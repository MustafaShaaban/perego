/**
 * Live-canvas markup for perego-theme/not-found (spec 021 C15; DECISIONS 2026-07-22).
 *
 * Renders the REAL 404 page — logo, background, decorative grid and orbs, the error code/title/text,
 * and both action buttons — the same skeleton `NotFoundRenderer::render()` emits.
 *
 * **Locked** preview: the copy is `GlobalContent::notFound()`. The two buttons keep the derived Home
 * and Contact routes (spec 021 T036 scope: route links stay derived).
 */

/** Mirror of `GlobalContent::notFound()` (English). */
export const SEED = {
	code: '404',
	title: 'This page took a creative detour',
	text: "The page you're looking for doesn't exist or has been moved.",
	backHome: 'Back to Home',
	contact: 'Contact Us',
	logoLabel: 'Perego — home',
};

export function NotFoundSkeleton() {
	return (
		<main className="error-page">
			<a className="logo error-logo" href="#" aria-label={ SEED.logoLabel }>
				<img src="" alt="Perego" />
			</a>
			<div className="error-page__bg" aria-hidden="true"><img src="" alt="" /></div>
			<div className="error-grid" aria-hidden="true"></div>
			<div className="error-orbs" aria-hidden="true">
				<span className="orb orb--1"></span>
				<span className="orb orb--2"></span>
				<span className="orb orb--3"></span>
				<span className="orb orb--4"></span>
			</div>
			<div className="error-page__inner">
				<p className="error-code" aria-hidden="true">{ SEED.code }</p>
				<h1 className="error-title">{ SEED.title }</h1>
				<p className="error-text">{ SEED.text }</p>
				<div className="error-actions">
					<a className="btn btn--accent" href="#">{ SEED.backHome }</a>
					<a className="btn btn--dark" href="#">{ SEED.contact }</a>
				</div>
			</div>
		</main>
	);
}
