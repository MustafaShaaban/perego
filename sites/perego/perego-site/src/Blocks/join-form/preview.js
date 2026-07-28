/**
 * Live-canvas markup for perego-theme/join-form (spec 021 C13; DECISIONS 2026-07-22).
 *
 * Renders the REAL careers application form — the same tag + class skeleton `JoinFormRenderer` emits
 * (name, email, portfolio, the `.file-drop` CV control with its hint, the honeypot, submit, and the
 * live status line) — so the editor canvas shows the actual form instead of the "rendered on the front
 * end" placeholder it used to show.
 *
 * **Locked** preview with no controls: every label, placeholder and message is a translation-catalogue
 * string, and the upload/validation lifecycle belongs to view.js. What an editor needs from the canvas
 * is where the form sits and how it is styled.
 */

/** Mirror of `JoinFormRenderer`'s English labels. */
export const SEED = {
	name: 'Full name',
	namePlaceholder: 'Put your name here',
	email: 'Email',
	emailPlaceholder: 'Put your Email here',
	portfolio: 'Portfolio/website link',
	portfolioPlaceholder: 'Put your Portfolio/website link',
	cv: 'CV',
	cvDrop: 'Upload your CV here',
	cvHint: 'Upload your CV here',
	submit: 'Apply now',
};

function Field( { id, label, required, children } ) {
	return (
		<div className="field join-form__field">
			<label htmlFor={ id }>
				{ label }
				{ required ? <> <span className="join-form__req" aria-hidden="true">*</span></> : null }
			</label>
			{ children }
			<span className="corex-form__error" id={ `${ id }-error` } role="alert"></span>
		</div>
	);
}

export function JoinFormSkeleton() {
	return (
		<form className="footer-form join-form" method="post" encType="multipart/form-data"
			aria-describedby="join-form-status" noValidate>
			<Field id="jf-name" label={ SEED.name } required>
				<input type="text" id="jf-name" name="name" required autoComplete="name" maxLength="80"
					placeholder={ SEED.namePlaceholder } aria-describedby="jf-name-error" />
			</Field>
			<Field id="jf-email" label={ SEED.email } required>
				<input type="email" id="jf-email" name="email" required autoComplete="email" maxLength="120"
					placeholder={ SEED.emailPlaceholder } aria-describedby="jf-email-error" />
			</Field>
			<Field id="jf-portfolio" label={ SEED.portfolio }>
				<input type="url" id="jf-portfolio" name="portfolio" inputMode="url"
					placeholder={ SEED.portfolioPlaceholder } aria-describedby="jf-portfolio-error" />
			</Field>
			<div className="field join-form__field">
				<label htmlFor="jf-cv">
					{ SEED.cv } <span className="join-form__req" aria-hidden="true">*</span>
				</label>
				<label className="file-drop" htmlFor="jf-cv">
					<span className="file-drop__text">{ SEED.cvDrop }</span>
					<span className="file-drop__filename" aria-live="polite"></span>
					<input type="file" id="jf-cv" name="cv" accept=".pdf,.doc,.docx" required
						aria-describedby="jf-cv-hint jf-cv-error" />
				</label>
				<p className="join-form__hint" id="jf-cv-hint">{ SEED.cvHint }</p>
				<span className="corex-form__error" id="jf-cv-error" role="alert"></span>
			</div>
			<input type="text" name="perego_hp" className="join-form__hp" tabIndex="-1"
				autoComplete="off" aria-hidden="true" defaultValue="" />
			<button type="submit" className="btn btn--accent footer-form__submit join-form__submit">
				{ SEED.submit }
			</button>
			<p className="join-form__status corex-form__status" id="join-form-status" role="status" aria-live="polite"></p>
		</form>
	);
}
