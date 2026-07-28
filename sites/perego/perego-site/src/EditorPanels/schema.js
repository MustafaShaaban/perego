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

/**
 * Mirror of `ProjectPostType::META_GALLERY` / `::META_PDFS`.
 *
 * Declared here rather than beside their panels in `index.js` so the PHP-mirror test covers them:
 * both are standalone media pickers rather than schema fields, and a key that lives only at its call
 * site is a key nothing checks against the sanitizer that will actually receive it.
 */
export const PROJECT_GALLERY_KEY = '_perego_gallery_attachment_ids';
export const PROJECT_PDFS_KEY = '_perego_project_pdf_ids';
export const PROJECT_TAXONOMY = 'perego_project_category';
export const WEB_CATEGORY_SLUG = 'web';

/** Mirror of `ServicePostType::POST_TYPE` / `::PORTFOLIO_MODES`. */
export const SERVICE_POST_TYPE = 'perego_service';

/** Mirror of `ClientPostType::POST_TYPE` / `::TAXONOMY` / `::TYPES`. */
export const CLIENT_POST_TYPE = 'perego_client';
export const CLIENT_TAXONOMY = 'perego_client_type';
export const CLIENT_TYPE_CORPORATE = 'corporate';
export const CLIENT_TYPE_INDIVIDUAL = 'individual';

/**
 * Mirror of `ProjectPostType::ICONS` — what the project's grid tiles advertise.
 *
 * Was inferred: a project with a video always got a ▶ and one with 2+ images always got the badge,
 * with no way to say otherwise. `none` leads because it is the quiet default.
 */
export const PROJECT_ICON_OPTIONS = [
	{ value: 'none', label: __( 'No icon', 'perego-site' ) },
	{ value: 'play', label: __( 'Show play icon', 'perego-site' ) },
	{ value: 'gallery', label: __( 'Gallery label', 'perego-site' ) },
];

/**
 * Mirror of `PostTypes\ProjectThumbnails::SHAPES`.
 *
 * A project appears in tiles of several different aspect ratios and the tile image is `object-fit:
 * cover`, so one landscape photo gets centre-cropped into a portrait slot and loses whatever mattered.
 * Each of these is optional — an empty one falls back to the nearest shape, then to the featured
 * image, so a project with none of them renders exactly as it always did.
 */
export const PROJECT_THUMB_FIELDS = [
	{ key: '_perego_thumb_hero', label: __( 'Wide feature — 578 × 332', 'perego-site' ) },
	{ key: '_perego_thumb_banner', label: __( 'Banner — 380 × 158', 'perego-site' ) },
	{ key: '_perego_thumb_tall', label: __( 'Tall — 182 × 332', 'perego-site' ) },
	{ key: '_perego_thumb_card', label: __( 'Standard card — 406 × 254', 'perego-site' ) },
];

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

/**
 * Mirror of `ClientPostType::BEHAVIORS` — what the card DOES.
 *
 * Phrased as outcomes an editor can picture, not as the slugs that are stored. `none` leads because
 * it is the default and the safe one: a card that shows information and nothing else.
 */
export const CLIENT_BEHAVIOR_OPTIONS = [
	{ value: 'none', label: __( 'No actions', 'perego-site' ) },
	{ value: 'lightbox', label: __( 'Open a lightbox', 'perego-site' ) },
	{ value: 'link', label: __( 'Go to a link', 'perego-site' ) },
];

/** Mirrors of the `ClientPostType` meta constants the Client panel writes directly. */
export const CLIENT_BEHAVIOR_KEY = '_perego_client_behavior';
export const CLIENT_SUBTITLE_KEY = '_perego_client_stat';

/**
 * Mirror of `ClientPostType::SUBTITLE_MAX_CHARS` — visible characters, so `<strong>` is free.
 *
 * A writing guide rather than the layout's protection: `.indiv-card__sub` is clamped to two lines in
 * CSS, which holds at every width, whereas a character count cannot (the box fits 24 characters per
 * line at 1440px and 15 at 1024px).
 */
export const CLIENT_SUBTITLE_MAX_CHARS = 48;
export const CLIENT_GALLERY_KEY = '_perego_client_gallery';
export const CLIENT_HIDE_PLAY_ICON_KEY = '_perego_client_hide_play_icon';
export const CLIENT_LINK_KEYS = {
	href: '_perego_client_link_url',
	linkKind: '_perego_client_link_kind',
	postType: '_perego_client_link_post_type',
	postId: '_perego_client_link_post_id',
	openInNewTab: '_perego_client_link_new_tab',
};

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
		title: __( 'In the grids', 'perego-site' ),
		fields: [
			{
				key: '_perego_project_featured',
				type: 'toggle',
				label: __( 'Feature on the home page', 'perego-site' ),
				// Mirror of `ProjectPostType::META_FEATURED`. Named for where it shows rather than as a
				// bare "Featured", because the home grid is the only surface that reads it — the Work
				// archive still lists every project either way.
				help: __( 'The home page shows only featured projects. The Work archive always shows them all.', 'perego-site' ),
			},
			{
				key: '_perego_project_icon',
				type: 'select',
				label: __( 'Icon', 'perego-site' ),
				options: PROJECT_ICON_OPTIONS,
				// Required so the list has no empty row: "No icon" IS the empty choice, and offering
				// both an unset state and a "none" state would mean the same thing twice.
				required: true,
				help: __( 'What the project’s tile shows on top of its image, in the work grid and the services grid.', 'perego-site' ),
			},
			...PROJECT_THUMB_FIELDS.map( ( field, index ) => ( {
				key: field.key,
				type: 'media',
				label: field.label,
				// Explained once, on the last one, rather than repeated on all four.
				help: index === PROJECT_THUMB_FIELDS.length - 1
					? __( 'Each crop is optional. An empty one falls back to the closest shape you did upload, then to the featured image — so a project with none of these looks exactly as it does now.', 'perego-site' )
					: undefined,
				// The several-shapes mosaic is a Video/Motion/Design surface. A Website Making project
				// renders through the showcase card instead, which is one fixed shape and shows the
				// client's logo rather than a crop — so these four never reach it, and offering them
				// here only invites an editor to prepare artwork nothing will ever display.
				showWhen: ( meta, context ) => ! context.isWebProject,
			} ) ),
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
			{
				key: '_perego_logo_id',
				type: 'media',
				label: __( 'Logo', 'perego-site' ),
				// The featured image stays the site screenshot, which the showcase card below needs.
				// Mirror of `ProjectPostType::META_LOGO`.
				help: __( 'The client’s logo. Shown on the home Work grid instead of the screenshot; falls back to the featured image when empty.', 'perego-site' ),
			},
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

/**
 * The Client panel's declarative text fields.
 *
 * Everything else on a client — type, logo/thumbnail, name, behaviour, gallery, link, play badge — is
 * either a native post property or a control with its own state, so it is composed directly in
 * `index.js` rather than described here. This list stays the schema so the PHP-mirror test keeps
 * guarding the meta keys it does cover.
 *
 * The two fields that used to live here are gone: `_perego_client_sub` became the card's normal
 * editor content, and the video source/URL pair became the behaviour + gallery model.
 */
export const CLIENT_PANELS = [
	{
		title: __( 'Client card', 'perego-site' ),
		initialOpen: true,
		fields: [
			{
				key: CLIENT_SUBTITLE_KEY,
				type: 'text',
				// Renamed from "Statistic" (owner, 2026-07-28) — same meta, same sanitizer.
				// `ClientPostType::sanitizeStat` permits inline <strong> and strips everything else.
				label: __( 'Subtitle', 'perego-site' ),
				help: __( 'Sits directly under the name. Wrap the part you want bold in <strong> — e.g. <strong>+1M</strong> views.', 'perego-site' ),
				// A writing guide; the card clamps this line to two lines whatever is typed.
				maxVisibleChars: CLIENT_SUBTITLE_MAX_CHARS,
				showWhen: ( meta, context ) => context.isIndividual,
			},
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
