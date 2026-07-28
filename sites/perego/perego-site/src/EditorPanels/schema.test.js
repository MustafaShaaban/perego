/**
 * The panel schema's contract with PHP (spec 021 Phase 4 / T019).
 *
 * The sidebar panels write post meta directly, so **a typo in a meta key silently writes to a key no
 * renderer reads** — the field would appear to save and the public page would never change. These
 * tests read the actual PHP constants out of the PostType classes and assert the schema matches, so
 * the two can never drift apart unnoticed. Same for the enums: a select offering a value the PHP
 * sanitizer rejects would silently discard the editor's choice on save.
 */
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
	CLIENT_BEHAVIOR_KEY,
	CLIENT_BEHAVIOR_OPTIONS,
	CLIENT_GALLERY_KEY,
	CLIENT_HIDE_PLAY_ICON_KEY,
	CLIENT_LINK_KEYS,
	CLIENT_PANELS,
	CLIENT_SUBTITLE_KEY,
	PORTFOLIO_MODE_OPTIONS,
	PROJECT_ICON_OPTIONS,
	PROJECT_GALLERY_KEY,
	PROJECT_PANELS,
	PROJECT_PDFS_KEY,
	PROJECT_THUMB_FIELDS,
	SERVICE_PANELS,
	LEGAL_PANEL,
} from './schema';

const SRC = join( __dirname, '..' );

const php = ( file ) => readFileSync( join( SRC, file ), 'utf8' );

/** Every `const NAME = 'value';` in a PHP class, as a map. */
const phpConstants = ( source ) => {
	const map = {};
	const re = /public const (\w+)\s*=\s*'([^']*)'/g;
	let match;
	while ( ( match = re.exec( source ) ) !== null ) {
		map[ match[ 1 ] ] = match[ 2 ];
	}
	return map;
};

/** Every string in a PHP `public const NAME = ['a', 'b'];` list. */
const phpConstantList = ( source, name ) => {
	const match = source.match( new RegExp( `public const ${ name }\\s*=\\s*\\[([^\\]]*)\\]` ) );
	return match ? [ ...match[ 1 ].matchAll( /'([^']+)'/g ) ].map( ( m ) => m[ 1 ] ) : [];
};

const fieldKeys = ( panels ) => panels.flatMap( ( panel ) => panel.fields.map( ( field ) => field.key ) );

describe( 'panel schema ↔ PHP meta keys', () => {
	test( 'every Project field key is a real ProjectPostType meta constant', () => {
		const constants = Object.values( phpConstants( php( 'PostTypes/ProjectPostType.php' ) ) );

		fieldKeys( PROJECT_PANELS ).forEach( ( key ) => expect( constants ).toContain( key ) );
	} );

	test( 'every Service field key is a real ServicePostType meta constant', () => {
		const constants = Object.values( phpConstants( php( 'PostTypes/ServicePostType.php' ) ) );

		fieldKeys( SERVICE_PANELS ).forEach( ( key ) => expect( constants ).toContain( key ) );
	} );

	test( 'every Client field key is a real ClientPostType meta constant', () => {
		const constants = Object.values( phpConstants( php( 'PostTypes/ClientPostType.php' ) ) );

		fieldKeys( CLIENT_PANELS ).forEach( ( key ) => expect( constants ).toContain( key ) );
	} );

	test( 'the legal field key matches LegalUpdatedRenderer::META_UPDATED', () => {
		const constants = Object.values( phpConstants( php( 'Blocks/LegalUpdatedRenderer.php' ) ) );

		expect( constants ).toContain( LEGAL_PANEL.fields[ 0 ].key );
	} );
} );

describe( 'panel schema ↔ PHP sanitizer enums', () => {
	test( 'the site-type select offers exactly ProjectPostType::SITE_TYPES', () => {
		const allowed = phpConstantList( php( 'PostTypes/ProjectPostType.php' ), 'SITE_TYPES' );
		const field = PROJECT_PANELS.flatMap( ( p ) => p.fields ).find( ( f ) => f.key === '_perego_site_type' );

		expect( field.options.map( ( option ) => option.value ).sort() ).toEqual( [ ...allowed ].sort() );
	} );

	test( 'the portfolio-mode select offers exactly ServicePostType::PORTFOLIO_MODES', () => {
		const allowed = phpConstantList( php( 'PostTypes/ServicePostType.php' ), 'PORTFOLIO_MODES' );

		expect( PORTFOLIO_MODE_OPTIONS.map( ( option ) => option.value ).sort() ).toEqual( [ ...allowed ].sort() );
	} );

	test( 'the project icon select offers exactly ProjectPostType::ICONS', () => {
		const allowed = phpConstantList( php( 'PostTypes/ProjectPostType.php' ), 'ICONS' );

		expect( PROJECT_ICON_OPTIONS.map( ( option ) => option.value ).sort() ).toEqual( [ ...allowed ].sort() );
	} );

	/*
	 * Each crop field writes a meta key the PHP resolver reads by name. A typo here would save to a
	 * key nothing reads: the picker would look like it worked and the tile would never change.
	 */
	test( 'every project crop field is a real ProjectThumbnails shape', () => {
		const shapes = php( 'PostTypes/ProjectThumbnails.php' );
		const constants = phpConstants( php( 'PostTypes/ProjectPostType.php' ) );

		PROJECT_THUMB_FIELDS.forEach( ( field ) => {
			const constName = Object.keys( constants ).find( ( name ) => constants[ name ] === field.key );

			expect( constName ).toBeDefined();
			expect( shapes ).toContain( `ProjectPostType::${ constName }` );
		} );
	} );

	test( 'the behaviour choice offers exactly ClientPostType::BEHAVIORS', () => {
		const allowed = phpConstantList( php( 'PostTypes/ClientPostType.php' ), 'BEHAVIORS' );

		expect( CLIENT_BEHAVIOR_OPTIONS.map( ( option ) => option.value ).sort() ).toEqual( [ ...allowed ].sort() );
	} );

	/*
	 * `none` must lead the list: a card that acts by accident is the exact defect this model
	 * replaced, so the safe option is the one an editor lands on.
	 *
	 * It must NOT be declared as a PHP meta `default`, though. Since WP 5.5 a registered default is
	 * returned by `get_post_meta()` for a key that was never written, which makes "unset"
	 * indistinguishable from "explicitly none" — that masked the migration's already-done marker and
	 * would have stopped `TranslatedMeta` inheriting from the English record, rendering every Arabic
	 * card inert. An empty value sanitizes to `none` anyway, so the effective default is unchanged.
	 */
	test( 'the inert behaviour leads the list and is NOT a registered PHP default', () => {
		const source = php( 'PostTypes/ClientPostType.php' );
		const behaviorArgs = source.match( /META_BEHAVIOR => \[([\s\S]*?)\],/ )[ 1 ];

		expect( CLIENT_BEHAVIOR_OPTIONS[ 0 ].value ).toBe( 'none' );
		expect( behaviorArgs ).not.toMatch( /'default'/ );
	} );

	/*
	 * The panel writes these keys directly rather than through the schema list, so they get the same
	 * PHP-constant guard the schema fields have — a typo here writes meta no renderer reads.
	 */
	test( 'the keys the Client panel writes directly are real ClientPostType constants', () => {
		const constants = Object.values( phpConstants( php( 'PostTypes/ClientPostType.php' ) ) );

		[
			CLIENT_BEHAVIOR_KEY,
			CLIENT_SUBTITLE_KEY,
			CLIENT_GALLERY_KEY,
			CLIENT_HIDE_PLAY_ICON_KEY,
			...Object.values( CLIENT_LINK_KEYS ),
		].forEach( ( key ) => expect( constants ).toContain( key ) );
	} );

	/*
	 * This one caught a real mistake. The schema first offered the ProjectPostType::CATEGORIES keys
	 * (video|motion|design|web) — and the test asserted against the same wrong source, so both agreed
	 * and passed. The live meta on all eight Service posts actually holds the ROUTE slug
	 * (video-editing|motion-graphics|…), which is what ServiceContent keys on. The test now reads
	 * ServiceContent::SLUG_KEY, the authority the renderers use.
	 */
	test( 'the canonical-service select offers exactly the ServiceContent::SLUG_KEY slugs', () => {
		const source = php( 'Content/ServiceContent.php' );
		const block = source.match( /private const SLUG_KEY\s*=\s*\[([\s\S]*?)\];/ )[ 1 ];
		const slugs = [ ...block.matchAll( /'([a-z-]+)'\s*=>/g ) ].map( ( m ) => m[ 1 ] );
		const field = SERVICE_PANELS.flatMap( ( p ) => p.fields ).find( ( f ) => f.key === '_perego_service_slug' );

		expect( slugs ).toHaveLength( 4 );
		expect( field.options.map( ( option ) => option.value ).sort() ).toEqual( [ ...slugs ].sort() );
	} );

	test( 'the service select is NOT the project-category vocabulary — the two are different', () => {
		const categories = php( 'PostTypes/ProjectPostType.php' ).match( /public const CATEGORIES\s*=\s*\[([\s\S]*?)\];/ )[ 1 ];
		const categoryKeys = [ ...categories.matchAll( /'([a-z]+)'\s*=>/g ) ].map( ( m ) => m[ 1 ] );
		const field = SERVICE_PANELS.flatMap( ( p ) => p.fields ).find( ( f ) => f.key === '_perego_service_slug' );

		expect( field.options.map( ( option ) => option.value ) ).not.toEqual( categoryKeys );
	} );
} );

/*
 * The several-shapes crop set is a Video/Motion/Design feature. A Website Making project renders
 * through WebShowcaseRenderer — one fixed card shape showing the client's logo — so the four crops
 * never reach it, and the front end has always behaved that way. What was wrong is that the editor
 * still offered them, inviting someone to prepare four crops nothing would display.
 */
describe( 'per-shape crops are offered only where they render', () => {
	const gridsPanel = () => PROJECT_PANELS.find( ( panel ) => panel.fields.some( ( f ) => f.key === '_perego_thumb_hero' ) );
	const visibleKeys = ( context ) =>
		gridsPanel()
			.fields.filter( ( field ) => ! field.showWhen || field.showWhen( {}, context ) )
			.map( ( field ) => field.key );

	test( 'every crop field is offered on a non-web project', () => {
		expect( visibleKeys( { isWebProject: false } ) ).toEqual(
			expect.arrayContaining( PROJECT_THUMB_FIELDS.map( ( field ) => field.key ) )
		);
	} );

	test( 'no crop field is offered on a web project', () => {
		const shown = visibleKeys( { isWebProject: true } );

		PROJECT_THUMB_FIELDS.forEach( ( field ) => expect( shown ).not.toContain( field.key ) );
	} );

	test( 'the icon and the featured flag stay on both, since both grids read them', () => {
		[ true, false ].forEach( ( isWebProject ) =>
			expect( visibleKeys( { isWebProject } ) ).toEqual(
				expect.arrayContaining( [ '_perego_project_icon', '_perego_project_featured' ] )
			)
		);
	} );
} );

/*
 * The two standalone media pickers. They are not schema fields — each is its own panel with its own
 * control — so the field-key sweep above cannot see them, and a key that lives only at its call site
 * is one nothing checks against the sanitizer that will actually receive it.
 */
describe( 'standalone media keys ↔ PHP meta constants', () => {
	test( 'the gallery and document keys are real ProjectPostType constants', () => {
		const constants = php( 'PostTypes/ProjectPostType.php' );

		[ PROJECT_GALLERY_KEY, PROJECT_PDFS_KEY ].forEach( ( key ) =>
			expect( constants ).toContain( `'${ key }'` )
		);
	} );

	test( 'documents are a separate key, not folded into the gallery', () => {
		// Deliberate: widening META_GALLERY to typed rows would be a migration for every project to
		// buy something only the document picker needs.
		expect( PROJECT_PDFS_KEY ).not.toBe( PROJECT_GALLERY_KEY );
	} );
} );
