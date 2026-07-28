/**
 * The client gallery's list operations, free of any editor imports.
 *
 * Kept separate from `ClientGallery.js` for the same reason `Editor/collection.js` is separate from
 * the repeaters that use it: this is where the mistakes actually live — losing videos when images are
 * re-picked, duplicating rows, dropping the order the editor set — and it can be tested directly,
 * while a module importing `@wordpress/block-editor` cannot be loaded in this project's Jest setup.
 *
 * The row shape is `ClientPostType::sanitizeGallery`'s: an image row is an attachment id, a video row
 * is a URL that may also carry the attachment id it came from.
 */

/** Mirror of `ClientPostType::GALLERY_ITEM_TYPES`. */
export const IMAGE = 'image';
export const VIDEO = 'video';

const imageRow = ( attachment ) => ( { type: IMAGE, id: attachment.id, url: '' } );
const videoRow = ( attachment ) => ( { type: VIDEO, id: attachment.id, url: attachment.url } );
const linkRow = ( url ) => ( { type: VIDEO, id: 0, url } );

/**
 * Swap in a new set of images, keeping every video row where it is.
 *
 * The image picker runs in `gallery` mode, which hands back the FULL selection rather than only what
 * was just added — appending it would duplicate every image each time the picker is reopened. The
 * videos are untouched because they were never part of that selection.
 *
 * @param {Array} items    The current gallery.
 * @param {Array} selected Attachments from the image picker.
 * @return {Array} The new gallery.
 */
export function withImages( items, selected ) {
	return [ ...items.filter( ( item ) => item.type !== IMAGE ), ...selected.map( imageRow ) ];
}

/**
 * Add uploaded videos to the end of the gallery.
 *
 * Each keeps the attachment id it came from, so the file stays resolvable if its URL later changes.
 *
 * @param {Array}        items    The current gallery.
 * @param {Array|Object} selected One or more attachments from the video picker.
 * @return {Array} The new gallery.
 */
export function withVideos( items, selected ) {
	return [ ...items, ...[ selected ].flat().map( videoRow ) ];
}

/**
 * Add a pasted video link to the end of the gallery.
 *
 * A blank or duplicate URL is ignored rather than added: the lightbox would show the same slide
 * twice, and the editor gets no feedback that it was already there.
 *
 * @param {Array}  items The current gallery.
 * @param {string} url   The pasted address.
 * @return {Array} The gallery, unchanged when there was nothing to add.
 */
export function withVideoLink( items, url ) {
	const trimmed = ( url || '' ).trim();

	if ( trimmed === '' || items.some( ( item ) => item.url === trimmed ) ) {
		return items;
	}

	return [ ...items, linkRow( trimmed ) ];
}
