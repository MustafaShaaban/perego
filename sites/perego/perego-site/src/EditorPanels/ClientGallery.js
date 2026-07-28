/**
 * ClientGallery — the media a client card opens in the site-wide lightbox.
 *
 * Replaces `Admin\ClientMediaMetaBox`'s hidden JSON field and hand-rolled `wp.media` script with the
 * shared Inspector repeater, so reordering and removing behave the same here as in every block, and
 * an editor sees thumbnails rather than the raw `{type, id, url}` rows that are actually stored.
 *
 * Three ways in, because they are three different editorial acts: pick images from the library,
 * upload/pick video files, or paste a YouTube/Vimeo link. They all land in ONE ordered list — the
 * lightbox resolves each URL's own type at view time (`media-lightbox/view.js`), so the order the
 * editor sets here is exactly the order a visitor pages through.
 *
 * The stored shape is `ClientPostType::sanitizeGallery`'s: an image row is an attachment id, a video
 * row is a URL that may also carry the attachment id it came from. Never a raw id typed by hand.
 */
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Flex, FlexItem, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { LabeledRepeater } from '../Editor/LabeledRepeater';
import { normalizeRepeater } from '../Editor/collection';
import { IMAGE, withImages, withVideoLink, withVideos } from './gallery-items';

/**
 * One row's summary. Resolves its own attachment so a library image shows as a thumbnail and an
 * uploaded video shows its filename — a deleted attachment simply shows nothing rather than a
 * broken thumbnail, which is also how the renderer treats it.
 */
function GalleryRow( { item } ) {
	const media = useSelect(
		( select ) => ( item.id ? select( 'core' ).getMedia( item.id ) : null ),
		[ item.id ]
	);

	const thumbnail = media?.media_details?.sizes?.thumbnail?.source_url || ( item.type === IMAGE ? media?.source_url : '' );
	const caption = item.type === IMAGE
		? media?.alt_text || media?.title?.rendered || __( 'Image', 'perego-site' )
		: item.url || media?.source_url || '';

	return (
		<Flex className="perego-client-gallery__row" gap={ 3 } align="center" justify="flex-start">
			{ thumbnail && (
				<FlexItem>
					<img className="perego-client-gallery__thumb" src={ thumbnail } alt="" width="48" height="48" />
				</FlexItem>
			) }
			<FlexItem isBlock>
				<span className="perego-client-gallery__type">
					{ item.type === IMAGE ? __( 'Image', 'perego-site' ) : __( 'Video', 'perego-site' ) }
				</span>
				<span className="perego-client-gallery__caption">{ caption }</span>
			</FlexItem>
		</Flex>
	);
}

/** The paste-a-link row, kept out of the repeater because it is an add control, not an item. */
function VideoLinkAdder( { onAdd } ) {
	const [ url, setUrl ] = useState( '' );
	const trimmed = url.trim();

	const add = () => {
		if ( trimmed === '' ) {
			return;
		}
		onAdd( trimmed );
		setUrl( '' );
	};

	return (
		<div className="perego-client-gallery__link">
			<TextControl
				__nextHasNoMarginBottom
				type="url"
				label={ __( 'Video link', 'perego-site' ) }
				help={ __( 'A YouTube or Vimeo address. Paste it, then Add — it joins the list above.', 'perego-site' ) }
				placeholder="https://"
				value={ url }
				onChange={ setUrl }
				onKeyDown={ ( event ) => {
					// Enter inside the sidebar would otherwise fall through to the editor.
					if ( event.key === 'Enter' ) {
						event.preventDefault();
						add();
					}
				} }
			/>
			<Button variant="secondary" onClick={ add } disabled={ trimmed === '' }>
				{ __( 'Add video link', 'perego-site' ) }
			</Button>
		</div>
	);
}

export function ClientGallery( { value, onChange } ) {
	const items = normalizeRepeater( value );

	return (
		<div className="perego-client-gallery">
			<LabeledRepeater
				items={ items }
				onChange={ onChange }
				itemLabel={ __( 'item', 'perego-site' ) }
				emptyLabel={ __( 'Nothing in the gallery yet. Add images, videos or a video link below.', 'perego-site' ) }
				renderItem={ ( item ) => <GalleryRow item={ item } /> }
			/>

			<MediaUploadCheck>
				<Flex className="perego-client-gallery__actions" gap={ 2 } justify="flex-start" wrap>
					<FlexItem>
						<MediaUpload
							multiple
							gallery
							allowedTypes={ [ 'image' ] }
							value={ items.filter( ( item ) => item.type === IMAGE ).map( ( item ) => item.id ) }
							onSelect={ ( selected ) => onChange( withImages( items, selected ) ) }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ __( 'Add images', 'perego-site' ) }
								</Button>
							) }
						/>
					</FlexItem>
					<FlexItem>
						<MediaUpload
							multiple
							allowedTypes={ [ 'video' ] }
							onSelect={ ( selected ) => onChange( withVideos( items, selected ) ) }
							render={ ( { open } ) => (
								<Button variant="secondary" onClick={ open }>
									{ __( 'Add videos', 'perego-site' ) }
								</Button>
							) }
						/>
					</FlexItem>
				</Flex>
			</MediaUploadCheck>

			<VideoLinkAdder onAdd={ ( url ) => onChange( withVideoLink( items, url ) ) } />
		</div>
	);
}
