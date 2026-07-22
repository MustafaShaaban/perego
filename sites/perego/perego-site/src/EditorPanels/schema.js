/**
 * The field schema for Perego's post-type sidebar panels (spec 021 Phase 4 / T019, T022).
 *
 * One declaration per post type: panels, and the typed fields inside them. Every `key` is the meta key
 * the PHP renderers already read — **nothing here renames or migrates anything**, so the public output
 * cannot move. The enums mirror the PHP sanitizers exactly; where they disagree the sanitizer wins on
 * save, so they are kept adjacent and cross-referenced.
 *
 * Each entry replaces a specific raw text input from the old `PostMetaBoxes`, noted on the field.
 */
import { __ } from '@wordpress/i18n';

/** Mirror of `ProjectPostType::POST_TYPE` / `::TAXONOMY` / `::SITE_TYPES` / `::CATEGORIES`. */
export const PROJECT_POST_TYPE = 'perego_project';
export const PROJECT_TAXONOMY = 'perego_project_category';
export const WEB_CATEGORY_SLUG = 'web';

/** Mirror of `ServicePostType::POST_TYPE` / `::PORTFOLIO_MODES`. */
export const SERVICE_POST_TYPE = 'perego_service';

/** Mirror of `ClientPostType::POST_TYPE` / `::VIDEO_TYPES`. */
export const CLIENT_POST_TYPE = 'perego_client';

/** Mirror of `ProjectPostType::SITE_TYPES`. */
const SITE_TYPE_OPTIONS = [
	{ value: 'ecommerce', label: __( 'E-commerce', 'perego-site' ) },
	{ value: 'corporate', label: __( 'Corporate', 'perego-site' ) },
	{ value: 'landing', label: __( 'Landing page', 'perego-site' ) },
	{ value: 'webapp', label: __( 'Web app', 'perego-site' ) },
	{ value: 'portfolio', label: __( 'Portfolio', 'perego-site' ) },
];

/** Mirror of `ServicePostType::PORTFOLIO_MODES`. */
export const PORTFOLIO_MODE_OPTIONS = [
	{ value: 'automatic', label: __( 'Automatic — latest matching projects', 'perego-site' ) },
	{ value: 'manual', label: __( 'Manual — only the projects I choose', 'perego-site' ) },
	{ value: 'hybrid', label: __( 'Hybrid — my choices first, then automatic', 'perego-site' ) },
];

/** Mirror of `ClientPostType::VIDEO_TYPES`. */
const VIDEO_TYPE_OPTIONS = [
	{ value: 'embed', label: __( 'Embed (YouTube / Vimeo)', 'perego-site' ) },
	{ value: 'upload', label: __( 'Uploaded file', 'perego-site' ) },
	{ value: 'external', label: __( 'External link', 'perego-site' ) },
];

/**
 * The four canonical Service slugs. This was a free-text `sanitize_key` field — an editor could type
 * anything, and only these four resolve to a real service.
 *
 * Mirror of **`ServiceContent::SLUG_KEY`**, not `ProjectPostType::CATEGORIES`. The two are different
 * vocabularies for the same four services and it is an easy and costly mix-up: the project *category*
 * terms are `video|motion|design|web`, but `_perego_service_slug` stores the *route* slug
 * (`video-editing|motion-graphics|graphic-design|website-making`), which is what `ServiceContent`,
 * the service tabs and the contact pre-selection all key on. Verified against the live meta on all
 * eight Service posts (EN + AR).
 */
const SERVICE_KEY_OPTIONS = [
	{ value: 'video-editing', label: __( 'Video Editing', 'perego-site' ) },
	{ value: 'motion-graphics', label: __( '2D Motion Graphics', 'perego-site' ) },
	{ value: 'graphic-design', label: __( 'Graphic Design', 'perego-site' ) },
	{ value: 'website-making', label: __( 'Website Making', 'perego-site' ) },
];

export const PROJECT_PANELS = [
	{
		title: __( 'Project details', 'perego-site' ),
		initialOpen: true,
		fields: [
			{ key: '_perego_client', type: 'text', label: __( 'Client', 'perego-site' ) },
			{ key: '_perego_year', type: 'text', label: __( 'Year', 'perego-site' ), placeholder: '2026' },
			{ key: '_perego_role', type: 'text', label: __( 'Our role', 'perego-site' ) },
			// Was a single-line text input; this is prose and routinely runs long.
			{ key: '_perego_deliverables', type: 'textarea', label: __( 'Deliverables', 'perego-site' ) },
		],
	},
	{
		title: __( 'Media', 'perego-site' ),
		fields: [
			{
				key: '_perego_video_url',
				type: 'url',
				label: __( 'Video URL', 'perego-site' ),
				help: __( 'A YouTube/Vimeo link or a video file. Set this and the project opens as a video card.', 'perego-site' ),
			},
		],
	},
	{
		title: __( 'Website showcase', 'perego-site' ),
		fields: [
			{
				key: '_perego_site_type',
				type: 'select',
				label: __( 'Site type', 'perego-site' ),
				options: SITE_TYPE_OPTIONS,
				// Was free text where only these five values resolve. Mirror of
				// `ProjectPostType::sanitizeSiteType`, which discards anything else on save.
				help: __( 'Used by the Website Making showcase card.', 'perego-site' ),
			},
			{ key: '_perego_site_url', type: 'url', label: __( 'Live site URL', 'perego-site' ) },
		],
		// Only meaningful for web-category projects — the showcase card is the Website Making surface.
		showWhen: ( meta, context ) => context.isWebProject,
	},
];

export const SERVICE_PANELS = [
	{
		title: __( 'Service', 'perego-site' ),
		initialOpen: true,
		fields: [
			{
				key: '_perego_service_slug',
				type: 'select',
				label: __( 'Canonical service', 'perego-site' ),
				options: SERVICE_KEY_OPTIONS,
				required: true,
				// Was free `sanitize_key` text. This key drives the service tabs, the contact
				// pre-selection, and the portfolio query — a typo silently breaks all three.
				help: __( 'Which of the four services this post is. Drives the service tabs and the portfolio query.', 'perego-site' ),
			},
			{
				key: '_perego_teaser_label',
				type: 'text',
				label: __( 'Homepage card label', 'perego-site' ),
				placeholder: __( 'Video Editing', 'perego-site' ),
			},
		],
	},
	{
		title: __( 'Homepage card', 'perego-site' ),
		fields: [
			// Was 'Homepage card image (attachment ID)' — a raw ID typed into a text box.
			{ key: '_perego_teaser_image_id', type: 'media', label: __( 'Card image', 'perego-site' ) },
			{
				key: '_perego_teaser_alt',
				type: 'text',
				label: __( 'Card image alt text', 'perego-site' ),
				help: __( 'Describes the image for screen readers. Leave empty only if the image is purely decorative.', 'perego-site' ),
			},
		],
	},
];

export const CLIENT_PANELS = [
	{
		title: __( 'Client card', 'perego-site' ),
		initialOpen: true,
		fields: [
			{
				key: '_perego_client_sub',
				type: 'text',
				label: __( 'Subtitle', 'perego-site' ),
				help: __( 'Shown on individual cards only, under the name.', 'perego-site' ),
			},
			{
				key: '_perego_client_stat',
				type: 'text',
				label: __( 'Statistic', 'perego-site' ),
				// Was: 'wrap the number in <strong> to bold it, e.g. <strong>+1M</strong> views'.
				// `ClientPostType::sanitizeStat` permits inline <strong> and strips everything else.
				help: __( 'Individual cards only. Wrap the number in <strong> to bold it — e.g. <strong>+1M</strong> views. Other HTML is removed.', 'perego-site' ),
			},
		],
	},
	{
		title: __( 'Client media', 'perego-site' ),
		fields: [
			{
				key: '_perego_client_video_type',
				type: 'select',
				label: __( 'Video source', 'perego-site' ),
				options: VIDEO_TYPE_OPTIONS,
				help: __( 'How the individual card opens its video.', 'perego-site' ),
			},
			{ key: '_perego_client_video_url', type: 'url', label: __( 'Video URL', 'perego-site' ) },
		],
	},
];

/** Mirror of `LegalUpdatedRenderer::META_UPDATED`. */
export const LEGAL_PANEL = {
	title: __( 'Legal page', 'perego-site' ),
	initialOpen: true,
	fields: [
		{
			key: '_perego_legal_updated',
			type: 'text',
			label: __( 'Last updated', 'perego-site' ),
			placeholder: '2026-07-01',
			help: __( 'Shown under the title. Leave empty to use the page’s own modified date.', 'perego-site' ),
		},
	],
};
