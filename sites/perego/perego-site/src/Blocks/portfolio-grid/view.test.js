/**
 * Jest — perego/portfolio-grid Interactivity API store (spec 003 / M3). Covers the service filter:
 * setting the active filter, chip pressed state, per-card hide logic, and the no-results message
 * gating. Reuses the spec-001 @wordpress/interactivity test double.
 */

function loadStore( context ) {
	jest.resetModules();
	const wpInteractivity = require( '@wordpress/interactivity' );
	wpInteractivity.__setMockContext( context );
	require( './view.js' );

	return wpInteractivity.__getStore( 'perego/portfolio-grid' );
}

function ctx( overrides = {} ) {
	return {
		activeFilter: 'all',
		present: [ 'video', 'motion', 'design' ],
		...overrides,
	};
}

describe( 'actions.setFilter', () => {
	test( 'sets the active filter to the clicked chip filter', () => {
		const context = ctx( { filter: 'video' } );
		const { actions } = loadStore( context );

		actions.setFilter();

		expect( context.activeFilter ).toBe( 'video' );
	} );
} );

describe( 'callbacks.filterPressed', () => {
	test( 'is true only for the chip matching the active filter', () => {
		const context = ctx( { activeFilter: 'motion', filter: 'motion' } );
		const { callbacks } = loadStore( context );
		expect( callbacks.filterPressed() ).toBe( true );

		context.filter = 'video';
		expect( callbacks.filterPressed() ).toBe( false );
	} );
} );

describe( 'callbacks.cardHidden', () => {
	test( 'shows every card when the filter is all', () => {
		const context = ctx( { activeFilter: 'all', category: 'design' } );
		const { callbacks } = loadStore( context );

		expect( callbacks.cardHidden() ).toBe( false );
	} );

	test( 'hides cards whose category does not match the active filter', () => {
		const context = ctx( { activeFilter: 'video', category: 'design' } );
		const { callbacks } = loadStore( context );
		expect( callbacks.cardHidden() ).toBe( true );

		context.category = 'video';
		expect( callbacks.cardHidden() ).toBe( false );
	} );
} );

describe( 'callbacks.noResultsHidden', () => {
	test( 'hidden while showing all', () => {
		const context = ctx( { activeFilter: 'all' } );
		const { callbacks } = loadStore( context );

		expect( callbacks.noResultsHidden() ).toBe( true );
	} );

	test( 'hidden when the active service has projects', () => {
		const context = ctx( { activeFilter: 'video' } );
		const { callbacks } = loadStore( context );

		expect( callbacks.noResultsHidden() ).toBe( true );
	} );

	test( 'shown (not hidden) when the active service has no projects', () => {
		const context = ctx( { activeFilter: 'web' } ); // web not in present
		const { callbacks } = loadStore( context );

		expect( callbacks.noResultsHidden() ).toBe( false );
	} );
} );
