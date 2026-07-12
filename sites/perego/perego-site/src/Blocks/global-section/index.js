/**
 * Editor registration for perego-theme/global-section. Server-rendered (PHP render_callback): the
 * block itself holds no prose — the visible copy is authored on the canvas of the linked
 * perego_global_section record. The sidebar carries only the structural `role` selector (which
 * global record this slot renders), never editorial text.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import './style.scss';

// Mirrors PeregoSite\PostTypes\GlobalSectionPostType::ROLES — structural role keys, not prose.
const ROLES = [
	{ value: '', label: __( '— Select a role —', 'perego-site' ) },
	{ value: 'header', label: __( 'Header content', 'perego-site' ) },
	{ value: 'standard-footer', label: __( 'Standard footer', 'perego-site' ) },
	{ value: 'contact-footer', label: __( 'Contact (flat) footer', 'perego-site' ) },
	{ value: 'global-cta', label: __( 'Global project CTA', 'perego-site' ) },
	{ value: 'contact-details', label: __( 'Global contact details', 'perego-site' ) },
	{ value: 'not-found', label: __( '404 editorial', 'perego-site' ) },
];

registerBlockType( 'perego-theme/global-section', {
	edit( { attributes, setAttributes } ) {
		const { role } = attributes;
		const label =
			ROLES.find( ( r ) => r.value === role )?.label ||
			__( 'No role selected', 'perego-site' );

		return (
			<div { ...useBlockProps() }>
				<InspectorControls>
					<PanelBody title={ __( 'Global section', 'perego-site' ) }>
						<SelectControl
							label={ __( 'Role', 'perego-site' ) }
							value={ role }
							options={ ROLES }
							onChange={ ( value ) => setAttributes( { role: value } ) }
							help={ __(
								'Which global record renders here, in the current language.',
								'perego-site'
							) }
						/>
					</PanelBody>
				</InspectorControls>
				<p className="perego-global-section__editor-placeholder">
					{ __( 'Global section:', 'perego-site' ) } <strong>{ label }</strong>
					<br />
					<span>
						{ __(
							'Edit the copy in the matching Global Section record; it renders here per language.',
							'perego-site'
						) }
					</span>
				</p>
			</div>
		);
	},
	save() {
		return null;
	},
} );
