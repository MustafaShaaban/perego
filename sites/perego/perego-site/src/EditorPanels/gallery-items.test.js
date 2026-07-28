/**
 * The client gallery's list operations (`ClientGallery`'s three "add" paths).
 *
 * The rendering around them is core components; what can actually go wrong is the list maths —
 * losing videos when images are re-picked, duplicating rows, or dropping the order the editor set.
 */
import { withImages, withVideoLink, withVideos } from './gallery-items';

const image = ( id ) => ( { type: 'image', id, url: '' } );
const video = ( url, id = 0 ) => ( { type: 'video', id, url } );

describe( 'withImages', () => {
	/*
	 * The image picker runs in `gallery` mode, which returns the FULL selection rather than only the
	 * additions. Appending it duplicated every existing image each time the picker was reopened.
	 */
	test( 'replaces the image rows rather than appending to them', () => {
		const items = [ image( 1 ), image( 2 ) ];

		expect( withImages( items, [ { id: 1 }, { id: 2 }, { id: 3 } ] ) ).toEqual( [
			image( 1 ),
			image( 2 ),
			image( 3 ),
		] );
	} );

	test( 'keeps every video row, which was never part of the image selection', () => {
		const items = [ video( 'https://youtu.be/a' ), image( 1 ), video( 'https://youtu.be/b' ) ];

		expect( withImages( items, [ { id: 9 } ] ) ).toEqual( [
			video( 'https://youtu.be/a' ),
			video( 'https://youtu.be/b' ),
			image( 9 ),
		] );
	} );

	test( 'clearing the selection removes the images and leaves the videos', () => {
		expect( withImages( [ image( 1 ), video( 'https://youtu.be/a' ) ], [] ) )
			.toEqual( [ video( 'https://youtu.be/a' ) ] );
	} );
} );

describe( 'withVideos', () => {
	test( 'appends uploaded videos with the attachment they came from', () => {
		const selected = [ { id: 7, url: 'https://perego.test/clip.mp4' } ];

		expect( withVideos( [ image( 1 ) ], selected ) ).toEqual( [
			image( 1 ),
			video( 'https://perego.test/clip.mp4', 7 ),
		] );
	} );

	// The picker hands back a bare object when only one file is chosen.
	test( 'accepts a single attachment as well as a list', () => {
		expect( withVideos( [], { id: 7, url: 'https://perego.test/clip.mp4' } ) )
			.toEqual( [ video( 'https://perego.test/clip.mp4', 7 ) ] );
	} );
} );

describe( 'withVideoLink', () => {
	test( 'appends a pasted link to the end of the list', () => {
		expect( withVideoLink( [ image( 1 ) ], 'https://youtu.be/abc' ) )
			.toEqual( [ image( 1 ), video( 'https://youtu.be/abc' ) ] );
	} );

	test( 'trims surrounding whitespace off a pasted address', () => {
		expect( withVideoLink( [], '  https://youtu.be/abc  ' ) )
			.toEqual( [ video( 'https://youtu.be/abc' ) ] );
	} );

	// Two identical slides give the visitor a dead page-forward and the editor no clue why.
	test( 'ignores a URL already in the gallery', () => {
		const items = [ video( 'https://youtu.be/abc' ) ];

		expect( withVideoLink( items, 'https://youtu.be/abc' ) ).toBe( items );
	} );

	test( 'ignores an empty or whitespace-only address', () => {
		const items = [ image( 1 ) ];

		expect( withVideoLink( items, '   ' ) ).toBe( items );
		expect( withVideoLink( items, '' ) ).toBe( items );
	} );
} );
