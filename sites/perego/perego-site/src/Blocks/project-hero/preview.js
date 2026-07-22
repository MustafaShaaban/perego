/**
 * Live-canvas markup for perego-theme/project-hero (spec 021 C8; DECISIONS 2026-07-22).
 *
 * Renders the REAL case-study hero — the same tag + class skeleton that `ProjectHeroRenderer::render()`
 * emits (breadcrumb, service-category eyebrow, the project title as `h1`, the featured image, and the
 * client/year/role/deliverables meta list) — so the editor canvas shows the actual hero, styled by the
 * theme's `main.css` via `add_editor_style`. It replaces an `edit()` that rendered a bare sentence
 * ("Project hero — … Rendered by ProjectHeroRenderer"), which showed the editor nothing at all.
 *
 * The block has no attributes: every value is read from the queried Project post (title, featured image,
 * `perego_project_category` term, and the `_perego_client` / `_perego_year` / `_perego_role` /
 * `_perego_deliverables` meta). So this is a **locked** preview with no controls — the copy is edited on the
 * Project itself. `ProjectHeroSkeleton` is pure so `parity.test.js` can render it to a string and assert it
 * against a fixture of the PHP output; per `../../Editor/parity.js` only tags, class hooks, and nesting are
 * compared, so the renderer's inline `style` attributes and volatile href/src values are ignored.
 *
 * The breadcrumb + meta labels mirror `PeregoSite\Content\PortfolioContent` (its PHP arrays are the source
 * of truth); parity compares structure only, so seed copy can never break the test.
 */
import { __ } from '@wordpress/i18n';

/** Breadcrumb + meta labels — mirror of `PortfolioContent` (English canvas). */
export const SEED_LABELS = {
	home: 'Home',
	work: 'Our Work',
	project: 'Project',
	client: 'Client',
	year: 'Year',
	role: 'Our role',
	deliverables: 'Deliverables',
};

/**
 * Build the meta rows the renderer emits, dropping empty values exactly as `metaItem()` does.
 *
 * @param {Object} values `{ client, year, role, deliverables }` raw post-meta values.
 * @return {Array<{label:string,value:string}>} Non-empty rows in the renderer's fixed order.
 */
export function metaRows( values = {} ) {
	return [
		{ label: SEED_LABELS.client, value: values.client },
		{ label: SEED_LABELS.year, value: values.year },
		{ label: SEED_LABELS.role, value: values.role },
		{ label: SEED_LABELS.deliverables, value: values.deliverables },
	].filter( ( row ) => ( row.value || '' ) !== '' );
}

/**
 * The real project-hero markup for the editor canvas. `category` and `featuredUrl` are optional — the
 * renderer omits their elements when the project has no category term / featured image, and so does this.
 */
export function ProjectHeroSkeleton( { title, category, featuredUrl, rows = [] } ) {
	return (
		<div className="post-hero">
			<div className="post-hero__inner">
				<nav className="page-crumb" aria-label="Breadcrumb">
					<a href="#">{ SEED_LABELS.home }</a>
					<span aria-hidden="true">/</span>
					<a href="#">{ SEED_LABELS.work }</a>
					<span aria-hidden="true">/</span>
					<span aria-current="page">{ SEED_LABELS.project }</span>
				</nav>
				{ category ? <span className="post-cat">{ category }</span> : null }
				<h1 className="post-title">{ title || __( 'Project title', 'perego-site' ) }</h1>
			</div>
			{ featuredUrl ? (
				<div className="post-featured">
					{ /* Class list mirrors get_the_post_thumbnail( …, 'large' ), which the renderer calls. */ }
					<img src={ featuredUrl } alt="" className="attachment-large size-large wp-post-image" loading="eager" />
				</div>
			) : null }
			<dl className="project-hero__meta">
				{ rows.map( ( row ) => (
					<div key={ row.label } className="project-hero__meta-item">
						<dt>{ row.label }</dt>
						<dd>{ row.value }</dd>
					</div>
				) ) }
			</dl>
		</div>
	);
}
