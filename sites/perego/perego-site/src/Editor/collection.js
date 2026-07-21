/**
 * Immutable collection helpers for Perego editor repeaters.
 *
 * These deliberately operate on editor-facing objects, not persisted raw IDs. Blocks own their
 * serialization and schema; the foundation only supplies predictable add/duplicate/remove/reorder
 * behavior for accessible controls and keyboard actions.
 */

export function moveItem( items, fromIndex, toIndex ) {
	if ( fromIndex < 0 || fromIndex >= items.length || toIndex < 0 || toIndex >= items.length || fromIndex === toIndex ) {
		return items;
	}

	const next = [ ...items ];
	const [ item ] = next.splice( fromIndex, 1 );
	next.splice( toIndex, 0, item );
	return next;
}

export function duplicateItem( items, index, createCopy ) {
	if ( index < 0 || index >= items.length ) {
		return items;
	}

	const next = [ ...items ];
	next.splice( index + 1, 0, createCopy( items[ index ] ) );
	return next;
}

export function removeItem( items, index ) {
	return items.filter( ( _, itemIndex ) => itemIndex !== index );
}

/**
 * Read-time normalization for a repeater attribute (spec 021; DECISIONS 2026-07-21).
 *
 * New repeaters store a structured array. Blocks saved before the structured migration hold a legacy
 * JSON string. This accepts either and always returns an array, so no saved content is lost when a
 * block is rebuilt onto structured attributes. Seed/default fallback stays the block's own concern
 * (e.g. `normalizeRepeater( value ).length ? … : SEED`).
 *
 * @param {Array|string|null|undefined} value Structured array, legacy JSON string, or empty.
 * @return {Array} The repeater items as an array (empty when absent or malformed).
 */
export function normalizeRepeater( value ) {
	if ( Array.isArray( value ) ) {
		return value;
	}

	if ( typeof value === 'string' && value.trim() !== '' ) {
		try {
			const parsed = JSON.parse( value );
			return Array.isArray( parsed ) ? parsed : [];
		} catch ( e ) {
			return [];
		}
	}

	return [];
}
