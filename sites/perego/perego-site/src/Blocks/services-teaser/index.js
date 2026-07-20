/**
 * Perego services-teaser block — editor registration. The heading + "See All Services" link text are
 * RichText-editable directly in the canvas (spec 020 round 4) — previously 100% hardcoded with zero
 * admin UI. This block instance lives in the shared `front-page.html` FSE template, so — matching
 * `footer-careers`'/`clients-carousel`'s pattern for the same problem — each string is edited as an
 * En/Ar pair; `ServicesTeaserRenderer` picks the current-locale variant. The four service cards
 * themselves stay a Service-CPT-driven preview note (spec 012 — editing a Service updates its card).
 * Server-rendered (save returns null).
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

const SEED_EN = { headingEn: 'Services we can help you with', seeAllEn: 'See All Services' };
const SEED_AR = { headingAr: 'خدمات يمكننا مساعدتك بها', seeAllAr: 'عرض كل الخدمات' };

function LangGroup( { dir, seed, headingKey, seeAllKey, attributes, setAttributes } ) {
	return (
		<fieldset className="perego-services-teaser__lang" dir={ dir }>
			<RichText tagName="h2" className="services-teaser__title"
				value={ attributes[ headingKey ] || seed[ headingKey ] }
				onChange={ ( value ) => setAttributes( { [ headingKey ]: value } ) }
				placeholder={ seed[ headingKey ] } />
			<RichText tagName="span" className="services-teaser__link"
				value={ attributes[ seeAllKey ] || seed[ seeAllKey ] }
				onChange={ ( value ) => setAttributes( { [ seeAllKey ]: value } ) }
				placeholder={ seed[ seeAllKey ] } />
		</fieldset>
	);
}

function ServicesComposer( { attributes, services, setAttributes } ) {
	const mode = attributes.servicesMode || 'automatic';
	const order = attributes.serviceOrder || [];
	const excluded = attributes.serviceExcludeIds || [];
	const serviceById = new Map( services.map( ( service ) => [ service.id, service ] ) );
	const selected = order.map( ( id ) => serviceById.get( id ) ).filter( Boolean );
	const label = ( service ) => service.title?.rendered || __( 'Untitled Service', 'perego-site' );
	const updateSelection = ( id, enabled ) => setAttributes( {
		serviceOrder: enabled ? [ ...order, id ] : order.filter( ( selectedId ) => selectedId !== id ),
	} );
	const updateExclusion = ( id, enabled ) => setAttributes( {
		serviceExcludeIds: enabled ? [ ...excluded, id ] : excluded.filter( ( excludedId ) => excludedId !== id ),
	} );

	return (
		<PanelBody title={ __( 'Service cards', 'perego-site' ) } initialOpen={ false }>
			<SelectControl label={ __( 'Card source', 'perego-site' ) } value={ mode }
				options={ [
					{ label: __( 'Automatic (current Service cards)', 'perego-site' ), value: 'automatic' },
					{ label: __( 'Manual (selected Services only)', 'perego-site' ), value: 'manual' },
					{ label: __( 'Hybrid (selected first, then automatic)', 'perego-site' ), value: 'hybrid' },
				] }
				onChange={ ( servicesMode ) => setAttributes( { servicesMode } ) } />
			{ services.length === 0 && <p>{ __( 'No published Services are available in this language yet.', 'perego-site' ) }</p> }
			{ mode !== 'automatic' && services.map( ( service ) => (
				<CheckboxControl key={ service.id } label={ label( service ) }
					checked={ order.includes( service.id ) }
					onChange={ ( enabled ) => updateSelection( service.id, enabled ) } />
			) ) }
			{ mode !== 'manual' && services.map( ( service ) => (
				<CheckboxControl key={ `exclude-${ service.id }` } label={ __( 'Show', 'perego-site' ) + `: ${ label( service ) }` }
					checked={ ! excluded.includes( service.id ) }
					onChange={ ( enabled ) => updateExclusion( service.id, ! enabled ) } />
			) ) }
			{ mode !== 'automatic' && selected.map( ( service, index ) => (
				<div className="perego-services-teaser__service-order" key={ service.id }>
					<span>{ label( service ) }</span>
					<Button size="small" disabled={ index === 0 }
						onClick={ () => setAttributes( { serviceOrder: moveItem( order, index, index - 1 ) } ) }>{ __( 'Move up', 'perego-site' ) }</Button>
					<Button size="small" disabled={ index === selected.length - 1 }
						onClick={ () => setAttributes( { serviceOrder: moveItem( order, index, index + 1 ) } ) }>{ __( 'Move down', 'perego-site' ) }</Button>
				</div>
			) ) }
		</PanelBody>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-services-teaser__editor' } );
	const services = useSelect(
		( select ) => select( 'core' ).getEntityRecords( 'postType', 'perego_service', {
			per_page: -1,
			status: 'publish',
			orderby: 'menu_order',
			order: 'asc',
		} ) || [],
		[]
	);

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<ServicesComposer attributes={ attributes } services={ services } setAttributes={ setAttributes } />
			</InspectorControls>
			<fieldset className="perego-services-teaser__lang-group">
				<legend>{ __( 'English', 'perego-site' ) }</legend>
				<LangGroup dir="ltr" seed={ SEED_EN } headingKey="headingEn" seeAllKey="seeAllEn"
					attributes={ attributes } setAttributes={ setAttributes } />
			</fieldset>
			<fieldset className="perego-services-teaser__lang-group">
				<legend>{ __( 'Arabic', 'perego-site' ) }</legend>
				<LangGroup dir="rtl" seed={ SEED_AR } headingKey="headingAr" seeAllKey="seeAllAr"
					attributes={ attributes } setAttributes={ setAttributes } />
			</fieldset>
			<p className="perego-services-teaser__note">
				{ __( 'The four service cards are managed on each Service’s own edit screen (Services in the admin menu).', 'perego-site' ) }
			</p>
			<div className="perego-services-teaser__preview" onClick={ ( event ) => {
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
