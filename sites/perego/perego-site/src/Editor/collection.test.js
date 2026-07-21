import { duplicateItem, moveItem, normalizeRepeater, removeItem } from './collection';

describe( 'normalizeRepeater — structured repeater with legacy JSON-string upgrade', () => {
	it( 'passes a structured array value through unchanged', () => {
		const value = [ { label: 'a' }, { label: 'b' } ];
		expect( normalizeRepeater( value ) ).toEqual( value );
	} );

	it( 'upgrades a legacy JSON-string value to a structured array', () => {
		expect( normalizeRepeater( '[{"label":"a","href":"/"}]' ) ).toEqual( [ { label: 'a', href: '/' } ] );
	} );

	it.each( [
		[ 'an empty string', '' ],
		[ 'invalid JSON', '{not json' ],
		[ 'null', null ],
		[ 'undefined', undefined ],
		[ 'valid JSON that is not an array', '{"label":"a"}' ],
	] )( 'returns an empty array for %s', ( _label, value ) => {
		expect( normalizeRepeater( value ) ).toEqual( [] );
	} );
} );

describe( 'Perego editor collection helpers', () => {
	const items = [ { label: 'One' }, { label: 'Two' }, { label: 'Three' } ];

	it( 'moves an item while preserving the original collection', () => {
		expect( moveItem( items, 0, 2 ).map( ( item ) => item.label ) ).toEqual( [ 'Two', 'Three', 'One' ] );
		expect( items.map( ( item ) => item.label ) ).toEqual( [ 'One', 'Two', 'Three' ] );
	} );

	it( 'returns the existing collection when an inaccessible reorder is requested', () => {
		expect( moveItem( items, 0, -1 ) ).toBe( items );
	} );

	it( 'duplicates a caller-defined independent copy immediately after its source', () => {
		const duplicated = duplicateItem( items, 1, ( item ) => ( { ...item, label: `${ item.label } copy` } ) );
		expect( duplicated.map( ( item ) => item.label ) ).toEqual( [ 'One', 'Two', 'Two copy', 'Three' ] );
		expect( duplicated[ 1 ] ).not.toBe( duplicated[ 2 ] );
	} );

	it( 'removes only the selected item', () => {
		expect( removeItem( items, 1 ).map( ( item ) => item.label ) ).toEqual( [ 'One', 'Three' ] );
	} );
} );
