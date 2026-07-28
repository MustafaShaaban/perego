/**
 * FieldValue — one captured value, rendered so an operator can act on it.
 *
 * Every admin surface that shows a submission's payload stringified the value and stopped there, so
 * a stored URL arrived as text an operator had to select and paste. That is fine for a name and
 * useless for a document: the careers form records the applicant's CV as a link, and the reviewer
 * looking at the submission had no way to open it from the screen they were on (client report
 * 2026-07-27). Anything that is an http(s) URL is therefore a real anchor here.
 *
 * Only http and https are linked. The values are visitor-supplied, so a `javascript:` or `data:`
 * string must render as inert text — and `rel="noopener noreferrer"` keeps a followed link from
 * reaching back into the admin session.
 */
import { __ } from '@wordpress/i18n';

const HTTP_URL = /^https?:\/\/\S+$/i;

/** The display text for a value of any shape. Objects keep their JSON — structure is the point. */
export function toText( value ) {
	if ( value === null || value === undefined ) {
		return '';
	}
	return typeof value === 'object' ? JSON.stringify( value ) : String( value );
}

export default function FieldValue( { value, empty = '—' } ) {
	const text = toText( value );

	if ( text === '' ) {
		return empty;
	}

	if ( ! HTTP_URL.test( text ) ) {
		return text;
	}

	return (
		<a
			href={ text }
			target="_blank"
			rel="noopener noreferrer"
			title={ __( 'Opens in a new tab', 'corex' ) }
		>
			{ text }
		</a>
	);
}
