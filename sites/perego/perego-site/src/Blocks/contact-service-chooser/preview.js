/**
 * Live-canvas markup for perego-theme/contact-service-chooser (spec 021 C13; DECISIONS 2026-07-22).
 *
 * Renders the REAL service chooser — the same tag + class skeleton `ContactServiceChooserRenderer`
 * emits (the `.contact-choose` panel, its heading, the `.svc-choice-list` of toggle buttons, the help
 * line, and the watermark) — so the editor canvas shows the actual panel instead of a bare sentence.
 *
 * **Locked** preview with no controls: the buttons are a projection of the four Services (each label
 * is edited on that Service's own screen), the heading and help line are translation-catalogue strings,
 * and the preselected button follows the visitor's `?service=` query. The canvas shows all four
 * unselected, which is the state a visitor arriving without a query sees.
 */

/** Mirror of the renderer's English labels and the four canonical service slugs. */
export const SEED = {
	title: 'Choose your service',
	groupLabel: 'Choose your service (select one or more)',
	help: 'Tap to select one or more services. Tap again to deselect.',
};

export const SEED_SERVICES = [
	{ slug: 'video-editing', label: 'Video Editing' },
	{ slug: 'motion-graphics', label: '2D Motion Graphics' },
	{ slug: 'graphic-design', label: 'Graphic Design' },
	{ slug: 'website-making', label: 'Website Making' },
];

/**
 * @param {Object}        props
 * @param {Array<Object>} props.services Slug/label pairs for the toggle buttons.
 * @param {string}        props.selected Slug rendered as pressed, mirroring `?service=`.
 */
export function ContactServiceChooserSkeleton( { services = SEED_SERVICES, selected = '' } ) {
	return (
		<div className="contact-choose reveal" data-perego-service-chooser>
			<h2 className="section-title">{ SEED.title }</h2>
			<div className="svc-choice-list" role="group" aria-describedby="perego-services-error" aria-label={ SEED.groupLabel }>
				{ services.map( ( service ) => (
					<button
						key={ service.slug }
						type="button"
						className={ service.slug === selected ? 'svc-choice is-selected' : 'svc-choice' }
						data-service={ service.slug }
						aria-pressed={ service.slug === selected ? 'true' : 'false' }
					>
						{ service.label }
					</button>
				) ) }
			</div>
			{ /* Empty on the canvas as on a first page load — it only ever holds a validation
			     message the visitor caused, and `:empty` keeps it out of the layout until then. */ }
			<p className="svc-choice-error" id="perego-services-error" role="alert" />
			<p className="svc-choice-help">{ SEED.help }</p>
			<img className="contact-choose__watermark" src="" alt="" />
		</div>
	);
}
