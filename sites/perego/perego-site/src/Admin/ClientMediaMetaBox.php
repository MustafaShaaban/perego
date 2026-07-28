<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Admin;

use PeregoSite\PostTypes\ClientPostType;

defined('ABSPATH') || exit;

/**
 * A visual media editor for client cards. The saved fields deliberately retain the established
 * gallery/video contract, so existing corporate and individual cards keep rendering unchanged.
 */
final class ClientMediaMetaBox
{
    private const NONCE_ACTION = 'perego_client_media_save';
    private const NONCE_FIELD = 'perego_client_media_nonce';
    private const GALLERY_FIELD = ClientPostType::META_GALLERY;
    private const VIDEO_URL_FIELD = ClientPostType::META_VIDEO_URL;
    private const VIDEO_TYPE_FIELD = ClientPostType::META_VIDEO_TYPE;

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addBox']);
        add_action('save_post', [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function addBox(): void
    {
        add_meta_box(
            'perego-client-media',
            __('Client media', 'perego-site'),
            function ($post): void {
                $this->renderBox((int) $post->ID);
            },
            ClientPostType::POST_TYPE,
            'normal',
            'default'
        );
    }

    public function enqueue(string $hook): void
    {
        if (! in_array($hook, ['post.php', 'post-new.php'], true) || get_post_type() !== ClientPostType::POST_TYPE) {
            return;
        }

        wp_enqueue_media();
        wp_register_script('perego-client-media-picker', false, ['jquery'], null, true);
        wp_enqueue_script('perego-client-media-picker');
        wp_add_inline_script('perego-client-media-picker', 'window.PeregoClientMedia = ' . wp_json_encode([
            'imageLabel' => __('Image', 'perego-site'),
            'videoLabel' => __('Video', 'perego-site'),
            'removeLabel' => __('Remove', 'perego-site'),
            'upLabel' => __('Move up', 'perego-site'),
            'downLabel' => __('Move down', 'perego-site'),
            'imageTitle' => __('Choose gallery images', 'perego-site'),
            'videoTitle' => __('Choose uploaded video', 'perego-site'),
        ]) . ';', 'before');
        wp_add_inline_script('perego-client-media-picker', $this->script());
        wp_register_style('perego-client-media-editor', false);
        wp_enqueue_style('perego-client-media-editor');
        wp_add_inline_style('perego-client-media-editor', $this->style());
    }

    private function renderBox(int $postId): void
    {
        $gallery = ClientPostType::sanitizeGallery(get_post_meta($postId, self::GALLERY_FIELD, true));
        $videoUrl = (string) get_post_meta($postId, self::VIDEO_URL_FIELD, true);
        $videoType = ClientPostType::sanitizeVideoType((string) get_post_meta($postId, self::VIDEO_TYPE_FIELD, true));

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);
        echo '<div class="perego-client-media">';
        echo '<p class="description">' . esc_html__('Use the featured image for card artwork, then add optional supporting media below.', 'perego-site') . '</p>';
        $this->renderCardPreview($postId);
        $this->renderGallerySection($gallery);
        $this->renderVideoSection($videoUrl, $videoType);
        echo '</div>';
    }

    /** A lightweight, data-truthful preview before the editor changes the client card's media. */
    private function renderCardPreview(int $postId): void
    {
        $title = (string) get_the_title($postId);
        $thumbnail = has_post_thumbnail($postId) ? (string) get_the_post_thumbnail_url($postId, 'medium') : '';

        echo '<section class="perego-client-media__preview" aria-label="' . esc_attr__('Client card preview', 'perego-site') . '">';
        echo '<h4>' . esc_html__('Card preview', 'perego-site') . '</h4>';
        echo '<div class="perego-client-media__preview-card">';
        if ($thumbnail !== '') {
            printf('<img src="%s" alt="" />', esc_url($thumbnail));
        } else {
            echo '<span class="perego-client-media__preview-placeholder" aria-hidden="true"></span>';
        }
        echo '<div><strong>' . esc_html($title !== '' ? $title : __('Client name', 'perego-site')) . '</strong>';
        echo '<p>' . esc_html__('The featured image supplies the card artwork. Corporate cards open the gallery; Individual cards use the video action below.', 'perego-site') . '</p></div>';
        echo '</div></section>';
    }

    /** @param list<array{type:string,id:int,url:string}> $gallery */
    private function renderGallerySection(array $gallery): void
    {
        echo '<section class="perego-client-media__section">';
        echo '<h4>' . esc_html__('Corporate gallery', 'perego-site') . '</h4>';
        echo '<p class="description">' . esc_html__('The selected sequence opens when a corporate client tile is selected.', 'perego-site') . '</p>';
        printf('<input type="hidden" id="perego-client-gallery-json" name="%1$s" value="%2$s" />', esc_attr(self::GALLERY_FIELD), esc_attr((string) wp_json_encode($gallery)));
        echo '<div id="perego-client-gallery-rows" class="perego-client-gallery__rows" aria-live="polite">';
        foreach ($gallery as $index => $item) {
            $this->renderGalleryRow($index, $item);
        }
        echo '</div>';
        echo '<p class="perego-client-media__actions">';
        printf('<button type="button" class="button button-secondary" id="perego-client-gallery-add-images">%s</button>', esc_html__('Add images', 'perego-site'));
        printf('<button type="button" class="button button-secondary" id="perego-client-gallery-add-video">%s</button>', esc_html__('Add uploaded video', 'perego-site'));
        echo '</p>';
        printf('<p class="perego-client-media__url"><label for="perego-client-gallery-video-url">%1$s</label><input type="url" id="perego-client-gallery-video-url" class="regular-text" placeholder="https://" /><button type="button" class="button" id="perego-client-gallery-add-video-url">%2$s</button></p>', esc_html__('Or add a hosted video URL', 'perego-site'), esc_html__('Add video link', 'perego-site'));
        echo '</section>';
    }

    /** @param array{type:string,id:int,url:string} $item */
    private function renderGalleryRow(int $index, array $item): void
    {
        $thumb = $item['type'] === 'image' && $item['id'] > 0 ? (string) wp_get_attachment_image_url($item['id'], 'thumbnail') : '';
        echo '<div class="perego-client-gallery__row" data-index="' . esc_attr((string) $index) . '">';
        if ($thumb !== '') {
            printf('<img src="%s" alt="" width="64" height="64" />', esc_url($thumb));
        } elseif ($item['type'] === 'video') {
            printf('<span class="perego-client-gallery__video-url">%s</span>', esc_html($item['url']));
        }
        echo '<span class="perego-client-gallery__type">' . esc_html($item['type'] === 'image' ? __('Image', 'perego-site') : __('Video', 'perego-site')) . '</span>';
        echo '<span class="perego-client-gallery__order">' . esc_html(sprintf(__('%d in gallery', 'perego-site'), $index + 1)) . '</span>';
        echo '<div class="perego-client-gallery__controls">';
        echo '<button type="button" class="button-link perego-client-gallery__move" data-direction="up">' . esc_html__('Up', 'perego-site') . '</button>';
        echo '<button type="button" class="button-link perego-client-gallery__move" data-direction="down">' . esc_html__('Down', 'perego-site') . '</button>';
        echo '<button type="button" class="button-link-delete perego-client-gallery__remove">' . esc_html__('Remove', 'perego-site') . '</button>';
        echo '</div></div>';
    }

    private function renderVideoSection(string $videoUrl, string $videoType): void
    {
        echo '<section class="perego-client-media__section">';
        echo '<h4>' . esc_html__('Individual client video', 'perego-site') . '</h4>';
        echo '<p class="description">' . esc_html__('This single action appears on an individual client card.', 'perego-site') . '</p>';
        printf('<p><label for="perego-client-video-url">%1$s</label><input type="url" id="perego-client-video-url" name="%2$s" value="%3$s" class="widefat" placeholder="https://" /></p>', esc_html__('Video URL', 'perego-site'), esc_attr(self::VIDEO_URL_FIELD), esc_attr($videoUrl));
        printf('<p><button type="button" class="button" id="perego-client-video-select">%s</button></p>', esc_html__('Choose uploaded video', 'perego-site'));
        echo '<p><label for="perego-client-video-type">' . esc_html__('Playback', 'perego-site') . '</label><select id="perego-client-video-type" name="' . esc_attr(self::VIDEO_TYPE_FIELD) . '">';
        foreach ([
            'embed' => __('Play an embedded or hosted video in the lightbox', 'perego-site'),
            'upload' => __('Play an uploaded video in the lightbox', 'perego-site'),
            'external' => __('Open the link in a new tab', 'perego-site'),
        ] as $value => $label) {
            printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($value), selected($videoType, $value, false), esc_html($label));
        }
        echo '</select></p></section>';
    }

    /** @param int $postId @param \WP_Post $post */
    public function save($postId, $post): void
    {
        $postId = (int) $postId;
        $postType = is_object($post) ? (string) $post->post_type : (string) get_post_type($postId);
        if ($postType !== ClientPostType::POST_TYPE || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($postId)) {
            return;
        }
        $nonce = isset($_POST[self::NONCE_FIELD]) ? sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_FIELD])) : '';
        if ($nonce === '' || ! wp_verify_nonce($nonce, self::NONCE_ACTION) || ! current_user_can('edit_post', $postId)) {
            return;
        }
        $this->saveGallery($postId);
        $this->saveVideo($postId);
    }

    private function saveGallery(int $postId): void
    {
        if (! array_key_exists(self::GALLERY_FIELD, $_POST)) {
            return;
        }
        $decoded = json_decode(wp_unslash((string) $_POST[self::GALLERY_FIELD]), true);
        $gallery = ClientPostType::sanitizeGallery(is_array($decoded) ? $decoded : []);
        if ($gallery === []) {
            delete_post_meta($postId, self::GALLERY_FIELD);
            return;
        }
        update_post_meta($postId, self::GALLERY_FIELD, $gallery);
    }

    private function saveVideo(int $postId): void
    {
        if (array_key_exists(self::VIDEO_URL_FIELD, $_POST)) {
            $url = esc_url_raw(wp_unslash((string) $_POST[self::VIDEO_URL_FIELD]));
            $url === '' ? delete_post_meta($postId, self::VIDEO_URL_FIELD) : update_post_meta($postId, self::VIDEO_URL_FIELD, $url);
        }
        if (array_key_exists(self::VIDEO_TYPE_FIELD, $_POST)) {
            update_post_meta($postId, self::VIDEO_TYPE_FIELD, ClientPostType::sanitizeVideoType(wp_unslash((string) $_POST[self::VIDEO_TYPE_FIELD])));
        }
    }

    private function script(): string
    {
        return <<<'JS'
( function () {
	var imageFrame, videoFrame, galleryVideoFrame;
	var labels = window.PeregoClientMedia || {};
	function getRows() { var field = document.getElementById( 'perego-client-gallery-json' ); try { return field && field.value ? JSON.parse( field.value ) : []; } catch ( e ) { return []; } }
	function render( rows ) {
		var field = document.getElementById( 'perego-client-gallery-json' ), container = document.getElementById( 'perego-client-gallery-rows' );
		if ( ! field || ! container ) { return; }
		field.value = JSON.stringify( rows ); container.innerHTML = '';
		rows.forEach( function ( item, index ) {
			var row = document.createElement( 'div' ), type = document.createElement( 'span' ), order = document.createElement( 'span' ), controls = document.createElement( 'div' );
			row.className = 'perego-client-gallery__row'; row.dataset.index = String( index );
			if ( item.type === 'image' && item.url ) { var image = document.createElement( 'img' ); image.src = item.url; image.width = 64; image.height = 64; image.alt = ''; row.appendChild( image ); }
			if ( item.type === 'video' ) { var url = document.createElement( 'span' ); url.className = 'perego-client-gallery__video-url'; url.textContent = item.url; row.appendChild( url ); }
			type.className = 'perego-client-gallery__type'; type.textContent = item.type === 'image' ? labels.imageLabel : labels.videoLabel; row.appendChild( type );
			order.className = 'perego-client-gallery__order'; order.textContent = String( index + 1 ); row.appendChild( order );
			controls.className = 'perego-client-gallery__controls';
			[ [ 'up', labels.upLabel ], [ 'down', labels.downLabel ], [ 'remove', labels.removeLabel ] ].forEach( function ( action ) { var button = document.createElement( 'button' ); button.type = 'button'; button.className = action[ 0 ] === 'remove' ? 'button-link-delete perego-client-gallery__remove' : 'button-link perego-client-gallery__move'; button.dataset.action = action[ 0 ]; button.textContent = action[ 1 ]; controls.appendChild( button ); } );
			row.appendChild( controls ); container.appendChild( row );
		} );
	}
	function addMedia( frameName, type, multiple ) {
		if ( ! window.wp || ! window.wp.media ) { return; }
		var frame = frameName === 'image' ? imageFrame : galleryVideoFrame;
		if ( ! frame ) { frame = wp.media( { title: type === 'image' ? labels.imageTitle : labels.videoTitle, multiple: multiple, library: { type: type } } ); frame.on( 'select', function () { var list = getRows(); frame.state().get( 'selection' ).forEach( function ( attachment ) { var data = attachment.toJSON(); list.push( type === 'image' ? { type: 'image', id: data.id, url: data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url } : { type: 'video', id: 0, url: data.url } ); } ); render( list ); } ); if ( frameName === 'image' ) { imageFrame = frame; } else { galleryVideoFrame = frame; } }
		frame.open();
	}
	document.addEventListener( 'click', function ( event ) {
		var target = event.target; if ( ! target ) { return; }
		if ( target.id === 'perego-client-gallery-add-images' ) { event.preventDefault(); addMedia( 'image', 'image', true ); return; }
		if ( target.id === 'perego-client-gallery-add-video' ) { event.preventDefault(); addMedia( 'video', 'video', false ); return; }
		if ( target.id === 'perego-client-gallery-add-video-url' ) { event.preventDefault(); var input = document.getElementById( 'perego-client-gallery-video-url' ); if ( input && input.value ) { var list = getRows(); list.push( { type: 'video', id: 0, url: input.value } ); input.value = ''; render( list ); } return; }
		if ( target.classList.contains( 'perego-client-gallery__remove' ) || target.classList.contains( 'perego-client-gallery__move' ) ) { event.preventDefault(); var row = target.closest( '.perego-client-gallery__row' ), index = row ? parseInt( row.dataset.index, 10 ) : -1, list = getRows(); if ( index < 0 ) { return; } if ( target.dataset.action === 'remove' ) { list.splice( index, 1 ); } else if ( target.dataset.action === 'up' && index > 0 ) { list.splice( index - 1, 0, list.splice( index, 1 )[ 0 ] ); } else if ( target.dataset.action === 'down' && index < list.length - 1 ) { list.splice( index + 1, 0, list.splice( index, 1 )[ 0 ] ); } render( list ); return; }
		if ( target.id !== 'perego-client-video-select' || ! window.wp || ! window.wp.media ) { return; }
		event.preventDefault(); if ( ! videoFrame ) { videoFrame = wp.media( { title: labels.videoTitle, multiple: false, library: { type: 'video' } } ); videoFrame.on( 'select', function () { var data = videoFrame.state().get( 'selection' ).first().toJSON(), field = document.getElementById( 'perego-client-video-url' ), type = document.getElementById( 'perego-client-video-type' ); if ( field ) { field.value = data.url; } if ( type ) { type.value = 'upload'; } } ); } videoFrame.open();
	} );
}() );
JS;
    }

    private function style(): string
    {
        return '.perego-client-media__preview{border:1px solid #dcdcde;border-radius:4px;margin:16px 0;padding:12px}.perego-client-media__preview h4{margin:0 0 8px}.perego-client-media__preview-card{align-items:center;display:flex;gap:12px}.perego-client-media__preview-card img,.perego-client-media__preview-placeholder{background:#f0f0f1;border-radius:3px;display:block;height:72px;object-fit:cover;width:72px}.perego-client-media__preview-card p{color:#50575e;margin:4px 0 0}.perego-client-media__section{border-top:1px solid #dcdcde;margin-top:16px;padding-top:16px}.perego-client-media__section h4{margin:0 0 4px}.perego-client-media__actions{display:flex;flex-wrap:wrap;gap:8px}.perego-client-media__url{display:flex;align-items:center;flex-wrap:wrap;gap:8px}.perego-client-gallery__rows{display:grid;gap:8px;margin:12px 0}.perego-client-gallery__row{align-items:center;border:1px solid #dcdcde;border-radius:4px;display:flex;gap:10px;padding:8px}.perego-client-gallery__row img{border-radius:3px;object-fit:cover}.perego-client-gallery__video-url{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.perego-client-gallery__type{font-weight:600}.perego-client-gallery__order{color:#50575e;font-size:12px}.perego-client-gallery__controls{display:flex;gap:8px;margin-left:auto}@media (max-width:600px){.perego-client-gallery__row{align-items:flex-start;flex-wrap:wrap}.perego-client-gallery__controls{margin-left:0;width:100%}}';
    }
}
