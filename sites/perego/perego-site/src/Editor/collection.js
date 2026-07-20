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
