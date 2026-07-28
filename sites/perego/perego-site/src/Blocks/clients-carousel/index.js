/**
 * Perego clients-carousel block — editor registration (spec 021 C6 / T015; DECISIONS 2026-07-22).
 *
 * The client cards are DYNAMIC — a projection of many published `perego_client` posts (with their own
 * logos), not content this one block instance owns — so per the static-vs-dynamic rule the canvas keeps a
 * `ServerSideRender` preview of the real carousel rather than a hand-rebuilt skeleton. What changes for
 * spec 021 is the editing surface: the four section headings (corporate/individual title + subtitle, En/Ar)
 * and the automatic/manual/hybrid client composer now live in the Inspector on the shared `../../Editor`
 * primitives (`PanelSection`, `LanguagePair`, `RecordPicker`) — the SSR preview updates live as they change.
 * Server-rendered (save returns null); the scroll-snap track + arrows are wired by view.js.
 *
 * This block chooses WHICH clients appear, never what a card does — the play badge used to be one
 * toggle here governing every individual card at once, and is now each client's own setting alongside
 * its behaviour (2026-07-28). A `showPlayIcon` left in an already-saved block is inert: the block is
 * server-rendered with `save: () => null`, so an unknown attribute needs no deprecation.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { SelectControl, TextareaControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { LanguagePair } from '../../Editor/LanguagePair';
import { PanelSection } from '../../Editor/PanelSection';
import { RecordPicker } from '../../Editor/RecordPicker';
import metadata from './block.json';
import './style.scss';

const SEED = {
	corporate: {
		headingEn: 'Corporate Clients',
		subtitleEn: "Organizations and companies we've collaborated with on professional projects.",
		headingAr: 'عملاء الشركات',
		subtitleAr: 'مؤسسات وشركات تعاونّا معها في مشاريع احترافية.',
	},
	individual: {
		headingEn: 'Individual Clients',
		subtitleEn: "Professionals and individuals we've worked with.",
		headingAr: 'عملاء أفراد',
		subtitleAr: 'محترفون وأفراد عملنا معهم.',
	},
};

const clientLabel = ( client ) => client.title?.rendered || __( 'Untitled Client', 'perego-site' );

function ClientComposer( { type, title, clients, attributes, setAttributes } ) {
	const seed = SEED[ type ];
	const mode = attributes[ `${ type }Mode` ] || 'automatic';
	const order = attributes[ `${ type }Order` ] || [];
	const excluded = attributes[ `${ type }ExcludeIds` ] || [];
	const emptyLabel = __( 'No published clients are available for this client type yet.', 'perego-site' );

	return (
		<PanelSection title={ title }>
			<LanguagePair label={ __( 'Heading', 'perego-site' ) }
				en={ attributes[ `${ type }HeadingEn` ] } ar={ attributes[ `${ type }HeadingAr` ] }
				onChangeEn={ ( value ) => setAttributes( { [ `${ type }HeadingEn` ]: value } ) }
				onChangeAr={ ( value ) => setAttributes( { [ `${ type }HeadingAr` ]: value } ) }
				placeholderEn={ seed.headingEn } placeholderAr={ seed.headingAr } />
			<LanguagePair label={ __( 'Subtitle', 'perego-site' ) } Control={ TextareaControl }
				en={ attributes[ `${ type }SubtitleEn` ] } ar={ attributes[ `${ type }SubtitleAr` ] }
				onChangeEn={ ( value ) => setAttributes( { [ `${ type }SubtitleEn` ]: value } ) }
				onChangeAr={ ( value ) => setAttributes( { [ `${ type }SubtitleAr` ]: value } ) }
				placeholderEn={ seed.subtitleEn } placeholderAr={ seed.subtitleAr } />
			<SelectControl __nextHasNoMarginBottom label={ __( 'Card source', 'perego-site' ) } value={ mode }
				options={ [
					{ label: __( 'Automatic (all published clients)', 'perego-site' ), value: 'automatic' },
					{ label: __( 'Manual (selected clients only)', 'perego-site' ), value: 'manual' },
					{ label: __( 'Hybrid (selected first, then automatic)', 'perego-site' ), value: 'hybrid' },
				] }
				onChange={ ( value ) => setAttributes( { [ `${ type }Mode` ]: value } ) } />
			{ mode !== 'automatic' && (
				<RecordPicker mode="manual" records={ clients } order={ order } getLabel={ clientLabel }
					selectedHeading={ __( 'Shown clients (in order)', 'perego-site' ) }
					addHeading={ __( 'Add a client', 'perego-site' ) }
					noneSelectedLabel={ __( 'No clients selected yet.', 'perego-site' ) }
					emptyLabel={ emptyLabel }
					onChangeOrder={ ( value ) => setAttributes( { [ `${ type }Order` ]: value } ) } />
			) }
			{ mode !== 'manual' && (
				<RecordPicker mode="automatic" records={ clients } excluded={ excluded } getLabel={ clientLabel }
					emptyLabel={ emptyLabel }
					onChangeExcluded={ ( value ) => setAttributes( { [ `${ type }ExcludeIds` ]: value } ) } />
			) }
			<p className="perego-editor-help">
				{ __( 'Each client’s tile and logo are edited on that Client’s own screen (Clients in the admin menu).', 'perego-site' ) }
			</p>
		</PanelSection>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-clients-carousel__editor' } );
	const clients = useSelect(
		( select ) => select( 'core' ).getEntityRecords( 'postType', 'perego_client', { per_page: 100, status: 'publish' } ) || [],
		[]
	);
	const clientTypes = useSelect(
		( select ) => select( 'core' ).getEntityRecords( 'taxonomy', 'perego_client_type', { per_page: 100 } ) || [],
		[]
	);
	const clientsForType = ( type ) => {
		const term = clientTypes.find( ( candidate ) => candidate.slug === type || candidate.slug?.startsWith( `${ type }-` ) );
		return term ? clients.filter( ( client ) => client.perego_client_type?.includes( term.id ) ) : [];
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ClientComposer type="corporate" title={ __( 'Corporate clients', 'perego-site' ) }
					clients={ clientsForType( 'corporate' ) } attributes={ attributes } setAttributes={ setAttributes } />
				<ClientComposer type="individual" title={ __( 'Individual clients', 'perego-site' ) }
					clients={ clientsForType( 'individual' ) } attributes={ attributes } setAttributes={ setAttributes } />
			</InspectorControls>
			<div className="perego-clients-carousel__preview" onClick={ ( event ) => {
				// Neutralize the SSR preview's real links/buttons so a click never navigates the editor away.
				if ( event.target.closest( 'a, button' ) ) {
					event.preventDefault();
				}
			} }>
				<ServerSideRender block={ metadata.name } attributes={ attributes } />
			</div>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
