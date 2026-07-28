/**
 * The typed-field layer for Perego's post-type sidebar panels (spec 021 Phase 4 / T019, T022).
 *
 * Mirrors the framework's own `Corex\Config\Settings\FieldSections` → `SettingsForm::control()`
 * pattern — a declarative schema plus one `match` on `field.type` — so a panel is a list of field
 * definitions rather than hand-written markup per post type.
 *
 * WHY THIS EXISTS. Project, Service and Client metadata was edited through four separate classic meta
 * boxes, each with its own nonce, save handler and inline JS, and `PostMetaBoxes` rendered **every**
 * field as a bare `<input type="text">` with the instructions crammed into the label — including a
 * Service card image typed in as a raw **attachment ID**, a site type typed as free text where only
 * five values are valid, and a Client statistic that asked the editor to type `<strong>` tags. This
 * replaces all of that with real controls in the block editor's document sidebar.
 *
 * Every value round-trips through `useEntityProp( 'postType', type, 'meta' )`, so there is no nonce,
 * no save handler and no page reload: the meta is already `show_in_rest` on all three post types.
 * **No meta key changes and no data migration** — the panels write exactly the keys the renderers
 * already read, so the public output cannot move.
 */
import {
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { MediaField } from '../Editor/MediaField';

/**
 * A media control that resolves the attachment itself, so an editor sees a thumbnail rather than the
 * ID they used to have to type. Stores the attachment ID, which is what the renderers read.
 */
function MediaControl( { field, value, onChange } ) {
	const media = useSelect(
		( select ) => ( value ? select( 'core' ).getMedia( value ) : null ),
		[ value ]
	);

	return (
		<>
			<MediaField
				label={ field.label }
				value={ value }
				media={ media ? { url: media.source_url, alt: media.alt_text, width: media.media_details?.width, height: media.media_details?.height } : null }
				allowedTypes={ field.allowedTypes || [ 'image' ] }
				onSelect={ ( selected ) => onChange( selected.id ) }
				onRemove={ () => onChange( 0 ) }
			/>
			{ /* MediaField is a bare picker with no help slot of its own, but these fields need one:
			     the per-shape project crops are only comprehensible with a sentence of explanation. */ }
			{ field.help && <p className="perego-editor-help">{ field.help }</p> }
		</>
	);
}

/**
 * A select whose option list ALWAYS includes whatever is currently stored, even when that value is
 * not in the allowed set. Several of these fields were free text before, so an existing post can hold
 * something the enum does not list — and silently rewriting it on load would corrupt real content.
 * The stray value is shown, marked, and only replaced when the editor actively picks something else.
 */
function EnumControl( { field, value, onChange } ) {
	const options = field.options.slice();
	const isKnown = options.some( ( option ) => option.value === value );

	if ( value && ! isKnown ) {
		options.unshift( {
			value,
			/* translators: %s: the unrecognised value currently stored on the post. */
			label: __( '%s (current value — not a standard option)', 'perego-site' ).replace( '%s', value ),
		} );
	}

	if ( ! field.required ) {
		options.unshift( { value: '', label: field.emptyLabel || __( '— None —', 'perego-site' ) } );
	}

	return (
		<SelectControl __nextHasNoMarginBottom
			label={ field.label } help={ field.help }
			value={ value ?? '' } options={ options }
			onChange={ onChange } />
	);
}

/** Visible length of a value that may carry inline markup — tags never count against a limit. */
const visibleLength = ( value ) => String( value ?? '' ).replace( /<[^>]*>/g, '' ).length;

/**
 * A text field with a hard budget of VISIBLE characters, shown as a live count.
 *
 * Not a plain `maxLength`: this field's value may contain `<strong>`, and `maxLength` counts the raw
 * string, so the 17 characters of an empty `<strong></strong>` would eat more than half the budget
 * and the editor would be cut off mid-word for no visible reason. Input that would exceed the budget
 * is refused rather than silently trimmed, so what the editor sees is what gets saved — and matches
 * what `ClientPostType::sanitizeStat` would have enforced anyway.
 */
function CappedTextControl( { field, value, onChange } ) {
	const used = visibleLength( value );
	const max = field.maxVisibleChars;
	const counter = `${ used } / ${ max }`;

	return (
		<TextControl __nextHasNoMarginBottom
			label={ field.label }
			help={ field.help ? `${ field.help } (${ counter })` : counter }
			placeholder={ field.placeholder }
			value={ value ?? '' }
			onChange={ ( next ) => {
				// Allow anything that fits, and always allow shortening — otherwise a value already
				// over the limit (an import, or a lowered limit) could never be edited back down.
				if ( visibleLength( next ) <= max || visibleLength( next ) < used ) {
					onChange( next );
				}
			} } />
	);
}

/**
 * Render one field. The `match`-style dispatch is the whole point: adding a field type is one case
 * here, not a new hand-written control in three panels.
 *
 * @param {Object}   props
 * @param {Object}   props.field    Field definition: `{ key, type, label, help, options, … }`.
 * @param {*}        props.value    Current value.
 * @param {Function} props.onChange Receives the new value.
 */
export function Field( { field, value, onChange } ) {
	if ( field.maxVisibleChars ) {
		return <CappedTextControl field={ field } value={ value } onChange={ onChange } />;
	}

	switch ( field.type ) {
		case 'media':
			return <MediaControl field={ field } value={ value } onChange={ onChange } />;

		case 'select':
			return <EnumControl field={ field } value={ value } onChange={ onChange } />;

		case 'toggle':
			return (
				<ToggleControl __nextHasNoMarginBottom
					label={ field.label } help={ field.help }
					checked={ !! value } onChange={ onChange } />
			);

		case 'textarea':
			return (
				<TextareaControl __nextHasNoMarginBottom
					label={ field.label } help={ field.help } placeholder={ field.placeholder }
					value={ value ?? '' } onChange={ onChange } />
			);

		case 'url':
			return (
				<TextControl __nextHasNoMarginBottom type="url"
					label={ field.label } help={ field.help } placeholder={ field.placeholder }
					value={ value ?? '' } onChange={ onChange } />
			);

		default:
			return (
				<TextControl __nextHasNoMarginBottom
					label={ field.label } help={ field.help } placeholder={ field.placeholder }
					value={ value ?? '' } onChange={ onChange } />
			);
	}
}

/**
 * Render a group of fields against a meta object, honouring each field's `showWhen` predicate.
 *
 * @param {Object}        props
 * @param {Array<Object>} props.fields   Field definitions.
 * @param {Object}        props.meta     The post's meta object from `useEntityProp`.
 * @param {Function}      props.setMeta  Receives a partial meta patch.
 * @param {Object}        props.context  Extra state a `showWhen` may need (e.g. the post's terms).
 */
export function FieldGroup( { fields, meta, setMeta, context = {} } ) {
	return fields
		.filter( ( field ) => ! field.showWhen || field.showWhen( meta, context ) )
		.map( ( field ) => (
			<Field
				key={ field.key }
				field={ field }
				value={ meta?.[ field.key ] }
				onChange={ ( value ) => setMeta( { [ field.key ]: value } ) }
			/>
		) );
}
