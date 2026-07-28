/**
 * LinkPicker — choose a link's destination by picking a page, not by typing a URL (spec 021 T036).
 *
 * Supersedes `LinkControl` for every editable CTA, nav item, and footer link. An editor picks:
 *   1. Link type — an existing page/record, or a custom URL.
 *   2. For a record: the content type, then the record itself, by its own title — never a raw ID
 *      (the same rule `RecordPicker` follows).
 *   3. For a custom URL: the free-text field this control replaces, with its help text unchanged.
 *   4. Whether the link opens in a new tab.
 *
 * The value shape is ADDITIVE over the old `{ label, href }` so nothing needs migrating: `href` is
 * still the custom URL, and `linkKind`/`postType`/`postId`/`openInNewTab` sit beside it. A link saved
 * before this existed has no `linkKind`, which `PeregoSite\Blocks\LinkTarget` reads as `custom` — the
 * exact path it already took. Keeping `href` populated also gives the dynamic mode a real fallback for
 * when a chosen record is later unpublished or deleted.
 *
 * The caller owns the value and the change handler, so this stays presentational and schema-agnostic,
 * the same contract as the other `../Editor` primitives.
 */
import { SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * Read one link out of a block's flat, prefixed attributes — the mirror of PHP
 * `LinkTarget::fromAttributes()`. A block with a single link stores it as `ctaUrl`/`ctaLinkKind`/… so
 * that `<prefix>Url` stays the same attribute the block had before the picker existed, which is what
 * keeps every saved block valid without a deprecation.
 *
 * @param {Object} attributes The block's attributes.
 * @param {string} prefix     Attribute prefix, e.g. `cta` for `ctaUrl`.
 * @return {Object} A link value for `<LinkPicker link={…} />`.
 */
export function linkFromAttributes( attributes, prefix = 'cta' ) {
	return {
		href: attributes[ `${ prefix }Url` ] || '',
		linkKind: attributes[ `${ prefix }LinkKind` ] || 'custom',
		postType: attributes[ `${ prefix }PostType` ] || '',
		postId: attributes[ `${ prefix }PostId` ] || 0,
		openInNewTab: !! attributes[ `${ prefix }OpenInNewTab` ],
	};
}

/**
 * Write a link back into a block's flat, prefixed attributes — the inverse of
 * {@link linkFromAttributes}, for passing straight to `setAttributes`.
 *
 * @param {Object} link   The picker's value.
 * @param {string} prefix Attribute prefix.
 * @return {Object} A `setAttributes` patch.
 */
export function linkToAttributes( link, prefix = 'cta' ) {
	return {
		[ `${ prefix }Url` ]: link.href ?? '',
		[ `${ prefix }LinkKind` ]: link.linkKind ?? 'custom',
		[ `${ prefix }PostType` ]: link.postType ?? '',
		[ `${ prefix }PostId` ]: link.postId ?? 0,
		[ `${ prefix }OpenInNewTab` ]: !! link.openInNewTab,
	};
}

/** Types that can never be a link destination, whatever the site registers. */
const NEVER_LINKABLE = [ 'attachment' ];

/** How many records the item list offers per type. Perego's largest type is ~150 Projects. */
const RECORD_LIMIT = 100;

/**
 * The site's linkable post types, as select options. `viewable` is WordPress's own answer to "does
 * this type have a front end", so a UI-only type (patterns, templates, form submissions) never
 * appears as a link destination.
 */
function useLinkablePostTypes() {
	return useSelect( ( select ) => {
		const types = select( 'core' ).getPostTypes( { per_page: 100 } );

		if ( ! types ) {
			return null;
		}

		return types
			.filter( ( type ) => type.viewable && ! NEVER_LINKABLE.includes( type.slug ) )
			.map( ( type ) => ( {
				value: type.slug,
				label: decodeEntities( type.labels?.singular_name || type.name || type.slug ),
			} ) );
	}, [] );
}

/**
 * Published records of one type, as select options, ordered by title so the list is scannable.
 *
 * @param {string} postType Type slug; falsy skips the query entirely.
 */
function useRecordOptions( postType ) {
	return useSelect( ( select ) => {
		if ( ! postType ) {
			return [];
		}

		const records = select( 'core' ).getEntityRecords( 'postType', postType, {
			per_page: RECORD_LIMIT,
			status: 'publish',
			orderby: 'title',
			order: 'asc',
		} );

		if ( ! records ) {
			return null;
		}

		return records.map( ( record ) => ( {
			value: String( record.id ),
			label: decodeEntities( record.title?.rendered || '' ) || __( '(no title)', 'perego-site' ),
		} ) );
	}, [ postType ] );
}

export function LinkPicker( {
	link = {},
	onChange,
	labelText,
	hrefText,
	hrefHelp,
	showLabel = true,
} ) {
	const kind = link.linkKind === 'dynamic' ? 'dynamic' : 'custom';
	const postTypes = useLinkablePostTypes();
	const records = useRecordOptions( kind === 'dynamic' ? link.postType : '' );
	const update = ( patch ) => onChange( { ...link, ...patch } );

	const loading = __( 'Loading…', 'perego-site' );
	const choose = __( '— Select —', 'perego-site' );

	return (
		<div className="perego-editor-linkpicker">
			{ showLabel && (
				<TextControl __nextHasNoMarginBottom
					label={ labelText || __( 'Label', 'perego-site' ) }
					value={ link.label || '' }
					onChange={ ( label ) => update( { label } ) } />
			) }

			<SelectControl __nextHasNoMarginBottom
				label={ __( 'Links to', 'perego-site' ) }
				value={ kind }
				options={ [
					{ label: __( 'A page on this site', 'perego-site' ), value: 'dynamic' },
					{ label: __( 'A custom URL', 'perego-site' ), value: 'custom' },
				] }
				onChange={ ( linkKind ) => update( { linkKind } ) } />

			{ kind === 'dynamic' ? (
				<>
					<SelectControl __nextHasNoMarginBottom
						label={ __( 'Content type', 'perego-site' ) }
						value={ link.postType || '' }
						options={ [
							{ label: postTypes ? choose : loading, value: '' },
							...( postTypes || [] ),
						] }
						// Changing the type invalidates the chosen record, so clear it rather than
						// leave an id pointing into a different type.
						onChange={ ( postType ) => update( { postType, postId: 0 } ) } />

					<SelectControl __nextHasNoMarginBottom
						label={ __( 'Page', 'perego-site' ) }
						value={ link.postId ? String( link.postId ) : '' }
						disabled={ ! link.postType }
						options={ [
							{ label: records ? choose : loading, value: '' },
							...( records || [] ),
						] }
						onChange={ ( postId ) => update( { postId: Number( postId ) || 0 } ) }
						help={ __( 'The link follows this page — including its Arabic translation, and its address if the page is later renamed.', 'perego-site' ) } />

					{ records !== null && records?.length === 0 && (
						<p className="perego-editor-help">
							{ __( 'Nothing published in this content type yet.', 'perego-site' ) }
						</p>
					) }
				</>
			) : (
				<TextControl __nextHasNoMarginBottom
					label={ hrefText || __( 'Link', 'perego-site' ) }
					value={ link.href || '' }
					onChange={ ( href ) => update( { href } ) }
					help={ hrefHelp } />
			) }

			<ToggleControl __nextHasNoMarginBottom
				label={ __( 'Open in a new tab', 'perego-site' ) }
				checked={ !! link.openInNewTab }
				onChange={ ( openInNewTab ) => update( { openInNewTab } ) } />
		</div>
	);
}
