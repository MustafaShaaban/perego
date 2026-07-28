/**
 * Guards the JS composition against the PHP it mirrors, by parsing the PHP for the mode enum and by
 * reproducing the cases `tests/Content/ServicePortfolioSelectionTest.php` covers.
 */
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { PORTFOLIO_MODES, resolveSelection, sanitizeMode } from './servicePortfolio';

const post = ( id ) => ( { id } );
const ids = ( list ) => list.map( ( item ) => item.id );

test( 'the mode enum matches ServicePostType', () => {
	const php = readFileSync( join( __dirname, '..', 'PostTypes/ServicePostType.php' ), 'utf8' );
	const block = php.match( /PORTFOLIO_MODES\s*=\s*\[([^\]]*)\]/ )[ 1 ];
	const phpModes = [ ...block.matchAll( /'(\w+)'/g ) ].map( ( m ) => m[ 1 ] );

	expect( PORTFOLIO_MODES ).toEqual( phpModes );
} );

test( 'an unrecognised mode falls back to automatic', () => {
	expect( sanitizeMode( 'whatever' ) ).toBe( 'automatic' );
	expect( sanitizeMode( undefined ) ).toBe( 'automatic' );
} );

test( 'automatic returns the category results, minus exclusions', () => {
	const result = resolveSelection( [ post( 1 ), post( 2 ), post( 3 ) ], [ post( 9 ) ], 'automatic', [ 2 ] );

	expect( ids( result ) ).toEqual( [ 1, 3 ] );
} );

test( 'manual returns only the chosen projects, in their chosen order', () => {
	const result = resolveSelection( [ post( 1 ), post( 2 ) ], [ post( 5 ), post( 4 ) ], 'manual' );

	expect( ids( result ) ).toEqual( [ 5, 4 ] );
} );

test( 'hybrid leads with the chosen ones, then the rest, with no duplicates', () => {
	const result = resolveSelection( [ post( 1 ), post( 2 ), post( 3 ) ], [ post( 3 ), post( 9 ) ], 'hybrid' );

	expect( ids( result ) ).toEqual( [ 3, 9, 1, 2 ] );
} );

test( 'hybrid still honours exclusions on the automatic half', () => {
	const result = resolveSelection( [ post( 1 ), post( 2 ) ], [ post( 5 ) ], 'hybrid', [ 2 ] );

	expect( ids( result ) ).toEqual( [ 5, 1 ] );
} );
