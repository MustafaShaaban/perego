import { duplicateItem, moveItem, normalizeRepeater, partitionRecords, removeItem } from './collection';

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

describe( 'partitionRecords — RecordPicker selection semantics', () => {
	const records = [
		{ id: 1, title: 'Alpha' },
		{ id: 2, title: 'Beta' },
		{ id: 3, title: 'Gamma' },
	];
	const titles = ( list ) => list.map( ( record ) => record.title );

	it( 'returns the chosen records in order and the rest as available', () => {
		const { selected, available } = partitionRecords( records, [ 3, 1 ] );
		expect( titles( selected ) ).toEqual( [ 'Gamma', 'Alpha' ] );
		expect( titles( available ) ).toEqual( [ 'Beta' ] );
	} );

	it( 'ignores order ids with no matching record', () => {
		expect( partitionRecords( records, [ 99, 2 ] ).selected.map( ( r ) => r.id ) ).toEqual( [ 2 ] );
	} );

	it( 'treats an empty order as nothing chosen (all available)', () => {
		expect( titles( partitionRecords( records, [] ).available ) ).toEqual( [ 'Alpha', 'Beta', 'Gamma' ] );
	} );

	it( 'honors a custom id accessor', () => {
		const keyed = [ { key: 'a', title: 'A' }, { key: 'b', title: 'B' } ];
		const { selected } = partitionRecords( keyed, [ 'b' ], ( record ) => record.key );
		expect( titles( selected ) ).toEqual( [ 'B' ] );
	} );
} );
