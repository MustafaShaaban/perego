/**
 * Perego project-navigation block — editor registration (spec 021 C10; DECISIONS 2026-07-22).
 *
 * This block previously had **no editor script at all**, so the block editor could not register it and the
 * single-project template showed "Your site doesn't include support for this block". The canvas now renders the
 * REAL markup (`preview.js`), styled by the theme's `main.css` via `add_editor_style`.
 *
 * `surface` ("adjacent" | "related" | "all") is the block's only attribute and it is a structural choice, not
 * content, so it gets an Inspector select and nothing else — everything visible is queried from the Project
 * posts. Server-rendered (save returns null).
 */
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { LinkPicker, linkFromAttributes, linkToAttributes } from '../../Editor/LinkPicker';
import { PanelSection } from '../../Editor/PanelSection';
import {
	ProjectAdjacentSkeleton,
	ProjectRelatedSkeleton,
	RELATED_CARD_COUNT,
	placeholderCards,
} from './preview';
import metadata from './block.json';

/** Mirror of `ProjectPostType::POST_TYPE` / `::TAXONOMY`. */
const PROJECT_POST_TYPE = 'perego_project';

function useRelatedCards() {
	return useSelect( ( select ) => {
		const core = select( 'core' );
		const currentId = select( 'core/editor' )?.getCurrentPostId?.();
		// A design preview of the card structure, not a reimplementation of `relatedFor()` — see preview.js.
		const projects = core.getEntityRecords( 'postType', PROJECT_POST_TYPE, {
			per_page: RELATED_CARD_COUNT + 1,
			status: 'publish',
			orderby: 'date',
			order: 'desc',
			_embed: true,
		} );

		if ( ! projects ) {
			return placeholderCards();
		}

		const cards = projects
			.filter( ( project ) => project.id !== currentId )
			.slice( 0, RELATED_CARD_COUNT )
			.map( ( project ) => ( {
				id: project.id,
				title: decodeEntities( project.title?.rendered || '' ) || __( 'Untitled Project', 'perego-site' ),
				categoryLabel: decodeEntities(
					project._embedded?.[ 'wp:term' ]?.flat?.()?.[ 0 ]?.name || ''
				),
				excerpt: '',
				thumbUrl: project._embedded?.[ 'wp:featuredmedia' ]?.[ 0 ]?.source_url || '',
				thumbAlt: decodeEntities( project.title?.rendered || '' ),
			} ) );

		return cards.length > 0 ? cards : placeholderCards();
	}, [] );
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'perego-project-navigation__editor' } );
	const surface = attributes.surface || 'all';
	const cards = useRelatedCards();

	return (
		<div { ...blockProps } onClick={ ( event ) => {
			// Neutralize the preview's real links so a click never navigates the editor away.
			if ( event.target.closest( 'a, button' ) ) {
				event.preventDefault();
			}
		} }>
			<InspectorControls>
				<PanelSection title={ __( 'Project navigation', 'perego-site' ) } initialOpen>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Show', 'perego-site' ) }
						help={ __( 'Which navigation surface this instance renders. The single-project template uses one of each.', 'perego-site' ) }
						value={ surface }
						options={ [
							{ label: __( 'Previous / next links and related projects', 'perego-site' ), value: 'all' },
							{ label: __( 'Previous / next links only', 'perego-site' ), value: 'adjacent' },
							{ label: __( 'Related projects only', 'perego-site' ), value: 'related' },
						] }
						onChange={ ( value ) => setAttributes( { surface: value } ) }
					/>
				</PanelSection>
				{ surface !== 'adjacent' && (
					<PanelSection title={ __( 'Closing call to action', 'perego-site' ) }>
						<LinkPicker
							showLabel={ false }
							link={ linkFromAttributes( attributes ) }
							hrefText={ __( 'Button link', 'perego-site' ) }
							hrefHelp={ __( 'Leave empty for the contact page. The button text comes from the project labels.', 'perego-site' ) }
							onChange={ ( next ) => setAttributes( linkToAttributes( next ) ) } />
					</PanelSection>
				) }
			</InspectorControls>
			{ surface !== 'related' && <ProjectAdjacentSkeleton /> }
			{ surface !== 'adjacent' && <ProjectRelatedSkeleton cards={ cards } /> }
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
