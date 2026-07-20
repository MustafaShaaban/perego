/**
 * Perego clients-carousel block — editor registration. The four section headings
 * (corporate/individual title + subtitle) are RichText-editable directly in the canvas (spec 020
 * round 4). This block instance lives in the shared `front-page.html` FSE template (not per-language
 * post content), so — matching `footer-careers`' already-proven pattern for the same problem — each
 * heading is edited as an En/Ar pair in one view; `ClientsCarouselRenderer` picks the current-locale
 * variant at render time. The client cards themselves stay a CPT-driven preview note, since they come
 * from many Client posts, not this one block instance (see DECISIONS.md).
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps, RichText } from '@wordpress/block-editor';
import { Button, CheckboxControl, PanelBody, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { moveItem } from '../../Editor/collection';
import metadata from './block.json';
import './style.scss';

const SEED_EN = {
	corporateHeadingEn: 'Corporate Clients',
	corporateSubtitleEn: "Organizations and companies we've collaborated with on professional projects.",
	individualHeadingEn: 'Individual Clients',
	individualSubtitleEn: "Professionals and individuals we've worked with.",
};

const SEED_AR = {
	corporateHeadingAr: 'عملاء الشركات',
	corporateSubtitleAr: 'مؤسسات وشركات تعاونّا معها في مشاريع احترافية.',
	individualHeadingAr: 'عملاء أفراد',
	individualSubtitleAr: 'محترفون وأفراد عملنا معهم.',
};

function LangGroup( { dir, seed, attributes, setAttributes, headingKey, subtitleKey, note } ) {
	return (
		<fieldset className="perego-clients-carousel__lang" dir={ dir }>
			<RichText tagName="h2" className="section-title"
				value={ attributes[ headingKey ] || seed[ headingKey ] }
				onChange={ ( value ) => setAttributes( { [ headingKey ]: value } ) }
				placeholder={ seed[ headingKey ] } />
			<RichText tagName="p" className="section-subtitle"
				value={ attributes[ subtitleKey ] || seed[ subtitleKey ] }
				onChange={ ( value ) => setAttributes( { [ subtitleKey ]: value } ) }
				placeholder={ seed[ subtitleKey ] } />
			{ note && <p className="perego-clients-carousel__note">{ note }</p> }
		</fieldset>
	);
}

function ClientComposer( { type, clients, attributes, setAttributes } ) {
	const title = type === 'corporate' ? __( 'Corporate clients', 'perego-site' ) : __( 'Individual clients', 'perego-site' );
	const modeKey = `${ type }Mode`;
	const orderKey = `${ type }Order`;
	const excludeKey = `${ type }ExcludeIds`;
	const mode = attributes[ modeKey ] || 'automatic';
	const order = attributes[ orderKey ] || [];
	const excluded = attributes[ excludeKey ] || [];
	const clientById = new Map( clients.map( ( client ) => [ client.id, client ] ) );
	const selected = order.map( ( id ) => clientById.get( id ) ).filter( Boolean );
	const label = ( client ) => client.title?.rendered || __( 'Untitled Client', 'perego-site' );

	return (
		<PanelBody title={ title } initialOpen={ false }>
			<SelectControl label={ __( 'Source', 'perego-site' ) } value={ mode }
				options={ [
					{ label: __( 'Automatic (all published clients)', 'perego-site' ), value: 'automatic' },
					{ label: __( 'Manual (selected clients only)', 'perego-site' ), value: 'manual' },
					{ label: __( 'Hybrid (selected first, then automatic)', 'perego-site' ), value: 'hybrid' },
				] }
				onChange={ ( value ) => setAttributes( { [ modeKey ]: value } ) } />
			{ clients.length === 0 && <p>{ __( 'No published clients are available for this client type.', 'perego-site' ) }</p> }
			{ mode !== 'automatic' && clients.map( ( client ) => (
				<CheckboxControl key={ client.id } label={ label( client ) }
					checked={ order.includes( client.id ) }
					onChange={ ( enabled ) => setAttributes( { [ orderKey ]: enabled ? [ ...order, client.id ] : order.filter( ( id ) => id !== client.id ) } ) } />
			) ) }
			{ mode !== 'manual' && clients.map( ( client ) => (
				<CheckboxControl key={ `show-${ client.id }` } label={ __( 'Show', 'perego-site' ) + `: ${ label( client ) }` }
					checked={ ! excluded.includes( client.id ) }
					onChange={ ( enabled ) => setAttributes( { [ excludeKey ]: enabled ? excluded.filter( ( id ) => id !== client.id ) : [ ...excluded, client.id ] } ) } />
			) ) }
			{ mode !== 'automatic' && selected.map( ( client, index ) => (
				<div className="perego-clients-carousel__client-order" key={ client.id }>
					<span>{ label( client ) }</span>
					<Button size="small" disabled={ index === 0 }
						onClick={ () => setAttributes( { [ orderKey ]: moveItem( order, index, index - 1 ) } ) }>{ __( 'Move up', 'perego-site' ) }</Button>
					<Button size="small" disabled={ index === selected.length - 1 }
						onClick={ () => setAttributes( { [ orderKey ]: moveItem( order, index, index + 1 ) } ) }>{ __( 'Move down', 'perego-site' ) }</Button>
				</div>
			) ) }
		</PanelBody>
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
	const cardsNote = __( 'Client tiles/cards are managed on each Client’s own edit screen (Clients in the admin menu).', 'perego-site' );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ClientComposer type="corporate" clients={ clientsForType( 'corporate' ) } attributes={ attributes } setAttributes={ setAttributes } />
				<ClientComposer type="individual" clients={ clientsForType( 'individual' ) } attributes={ attributes } setAttributes={ setAttributes } />
			</InspectorControls>
			<fieldset className="perego-clients-carousel__lang-group">
				<legend>{ __( 'English', 'perego-site' ) }</legend>
				<LangGroup dir="ltr" seed={ SEED_EN } attributes={ attributes } setAttributes={ setAttributes }
					headingKey="corporateHeadingEn" subtitleKey="corporateSubtitleEn" />
				<LangGroup dir="ltr" seed={ SEED_EN } attributes={ attributes } setAttributes={ setAttributes }
					headingKey="individualHeadingEn" subtitleKey="individualSubtitleEn" note={ cardsNote } />
			</fieldset>
			<fieldset className="perego-clients-carousel__lang-group">
				<legend>{ __( 'Arabic', 'perego-site' ) }</legend>
				<LangGroup dir="rtl" seed={ SEED_AR } attributes={ attributes } setAttributes={ setAttributes }
					headingKey="corporateHeadingAr" subtitleKey="corporateSubtitleAr" />
				<LangGroup dir="rtl" seed={ SEED_AR } attributes={ attributes } setAttributes={ setAttributes }
					headingKey="individualHeadingAr" subtitleKey="individualSubtitleAr" note={ cardsNote } />
			</fieldset>
			<div className="perego-clients-carousel__preview" onClick={ ( event ) => {
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
