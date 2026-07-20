<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Admin;

use PeregoSite\PostTypes\ClientPostType;

defined('ABSPATH') || exit;

/**
 * The Client editor's media UI (spec 020 round 2): a `wp.media` gallery repeater for the corporate
 * card's lightbox gallery (`ClientPostType::META_GALLERY` — a mix of images and video URLs), plus the
 * individual card's single video source (`META_VIDEO_URL` + `META_VIDEO_TYPE`, the last one deciding
 * whether the video plays inline via the lightbox or opens as a plain external link in a new tab).
 * Both card types share one box so an editor sees the whole client-media picture regardless of which
 * `perego_client_type` term is currently selected. Modeled on `ProjectGalleryMetaBox`: no build step,
 * `wp.media` only, save guarded by a nonce, the post capability, and autosave/revision checks.
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
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        if (get_post_type() !== ClientPostType::POST_TYPE) {
            return;
        }

        wp_enqueue_media();
        wp_register_script('perego-client-media-picker', false, ['jquery'], null, true);
        wp_enqueue_script('perego-client-media-picker');
        wp_add_inline_script('perego-client-media-picker', $this->script());
    }

    private function renderBox(int $postId): void
    {
        $gallery = ClientPostType::sanitizeGallery(get_post_meta($postId, self::GALLERY_FIELD, true));
        $videoUrl = (string) get_post_meta($postId, self::VIDEO_URL_FIELD, true);
        $videoType = ClientPostType::sanitizeVideoType((string) get_post_meta($postId, self::VIDEO_TYPE_FIELD, true));

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        echo '<div class="perego-client-media">';
        $this->renderGallerySection($gallery);
        $this->renderVideoSection($videoUrl, $videoType);
        echo '</div>';
    }

    /** @param list<array{type:string,id:int,url:string}> $gallery */
    private function renderGallerySection(array $gallery): void
    {
        echo '<h4>' . esc_html__('Gallery (used for Corporate tiles)', 'perego-site') . '</h4>';
        echo '<p class="description">' . esc_html__('A mix of images and video links/uploads — opens in the lightbox when the tile is clicked.', 'perego-site') . '</p>';

        printf(
            '<input type="hidden" id="perego-client-gallery-json" name="%1$s" value="%2$s" />',
            esc_attr(self::GALLERY_FIELD),
            esc_attr((string) wp_json_encode($gallery))
        );

        echo '<div id="perego-client-gallery-rows" class="perego-client-gallery__rows">';
        foreach ($gallery as $index => $item) {
            $this->renderGalleryRow($index, $item);
        }
        echo '</div>';

        printf(
            '<button type="button" class="button" id="perego-client-gallery-add">%s</button>',
            esc_html__('Add gallery item', 'perego-site')
        );
    }

    /** @param array{type:string,id:int,url:string} $item */
    private function renderGalleryRow(int $index, array $item): void
    {
        $thumb = $item['type'] === 'image' && $item['id'] > 0
            ? (string) wp_get_attachment_image_url($item['id'], 'thumbnail')
            : '';

        echo '<div class="perego-client-gallery__row" data-index="' . esc_attr((string) $index) . '" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">';
        if ($thumb !== '') {
            printf('<img src="%s" alt="" width="48" height="48" style="width:48px;height:48px;object-fit:cover;border-radius:4px;" />', esc_url($thumb));
        } elseif ($item['type'] === 'video') {
            printf('<code style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">%s</code>', esc_html($item['url']));
        }
        echo '<span>' . esc_html($item['type'] === 'image' ? __('Image', 'perego-site') : __('Video', 'perego-site')) . '</span>';
        echo '<button type="button" class="button-link perego-client-gallery__remove">' . esc_html__('Remove', 'perego-site') . '</button>';
        echo '</div>';
    }

    private function renderVideoSection(string $videoUrl, string $videoType): void
    {
        echo '<h4>' . esc_html__('Primary video (used for Individual cards)', 'perego-site') . '</h4>';

        printf(
            '<p><label for="perego-client-video-url" style="display:block;font-weight:600;margin-bottom:4px;">%1$s</label>'
            . '<input type="text" id="perego-client-video-url" name="%2$s" value="%3$s" class="widefat" /></p>',
            esc_html__('Video URL', 'perego-site'),
            esc_attr(self::VIDEO_URL_FIELD),
            esc_attr($videoUrl)
        );

        printf('<button type="button" class="button" id="perego-client-video-select">%s</button>', esc_html__('Select uploaded video', 'perego-site'));

        echo '<p><label for="perego-client-video-type" style="display:block;font-weight:600;margin:10px 0 4px;">' . esc_html__('How it opens', 'perego-site') . '</label>';
        echo '<select id="perego-client-video-type" name="' . esc_attr(self::VIDEO_TYPE_FIELD) . '">';
        $options = [
            'embed' => __('Embedded link (YouTube, Vimeo, …) — plays in the lightbox', 'perego-site'),
            'upload' => __('Uploaded video file — plays in the lightbox', 'perego-site'),
            'external' => __('External link — opens in a new tab instead of the lightbox', 'perego-site'),
        ];
        foreach ($options as $value => $label) {
            printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr($value), selected($videoType, $value, false), esc_html($label));
        }
        echo '</select></p>';
    }

    /**
     * @param int      $postId
     * @param \WP_Post $post
     */
    public function save($postId, $post): void
    {
        $postId = (int) $postId;
        $postType = is_object($post) ? (string) $post->post_type : (string) get_post_type($postId);
        if ($postType !== ClientPostType::POST_TYPE) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (wp_is_post_revision($postId)) {
            return;
        }
        $nonce = isset($_POST[self::NONCE_FIELD]) ? sanitize_text_field(wp_unslash((string) $_POST[self::NONCE_FIELD])) : '';
        if ($nonce === '' || ! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }
        if (! current_user_can('edit_post', $postId)) {
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

        $raw = wp_unslash((string) $_POST[self::GALLERY_FIELD]);
        $decoded = json_decode($raw, true);
        $gallery = ClientPostType::sanitizeGallery(is_array($decoded) ? $decoded : []);

        if ($gallery === []) {
            delete_post_meta($postId, self::GALLERY_FIELD);
        } else {
            update_post_meta($postId, self::GALLERY_FIELD, $gallery);
        }
    }

    private function saveVideo(int $postId): void
    {
        if (array_key_exists(self::VIDEO_URL_FIELD, $_POST)) {
            $url = esc_url_raw(wp_unslash((string) $_POST[self::VIDEO_URL_FIELD]));
            if ($url === '') {
                delete_post_meta($postId, self::VIDEO_URL_FIELD);
            } else {
                update_post_meta($postId, self::VIDEO_URL_FIELD, $url);
            }
        }

        if (array_key_exists(self::VIDEO_TYPE_FIELD, $_POST)) {
            $type = ClientPostType::sanitizeVideoType(wp_unslash((string) $_POST[self::VIDEO_TYPE_FIELD]));
            update_post_meta($postId, self::VIDEO_TYPE_FIELD, $type);
        }
    }

    /**
     * The inline picker script: a gallery repeater (add row → choose Image via `wp.media` or type a
     * Video URL; remove any row) that serializes to the hidden JSON field, plus a convenience
     * `wp.media` video picker that fills the single "Video URL" text field. No framework, no build.
     */
    private function script(): string
    {
        return <<<'JS'
( function () {
	var imageFrame;
	var videoFrame;

	function rows() {
		var hidden = document.getElementById( 'perego-client-gallery-json' );
		if ( ! hidden || ! hidden.value ) { return []; }
		try {
			var parsed = JSON.parse( hidden.value );
			return Array.isArray( parsed ) ? parsed : [];
		} catch ( e ) {
			return [];
		}
	}

	function renderRows( list ) {
		var hidden = document.getElementById( 'perego-client-gallery-json' );
		var container = document.getElementById( 'perego-client-gallery-rows' );
		if ( ! hidden || ! container ) { return; }

		hidden.value = JSON.stringify( list );
		container.innerHTML = '';

		list.forEach( function ( item, index ) {
			var row = document.createElement( 'div' );
			row.className = 'perego-client-gallery__row';
			row.dataset.index = String( index );
			row.style.cssText = 'display:flex;align-items:center;gap:8px;margin-bottom:8px;';

			if ( item.type === 'image' && item.url ) {
				var img = document.createElement( 'img' );
				img.src = item.url; img.width = 48; img.height = 48;
				img.style.cssText = 'width:48px;height:48px;object-fit:cover;border-radius:4px;';
				row.appendChild( img );
			} else if ( item.type === 'video' ) {
				var code = document.createElement( 'code' );
				code.textContent = item.url;
				code.style.cssText = 'max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
				row.appendChild( code );
			}

			var label = document.createElement( 'span' );
			label.textContent = item.type === 'image' ? 'Image' : 'Video';
			row.appendChild( label );

			var remove = document.createElement( 'button' );
			remove.type = 'button';
			remove.className = 'button-link perego-client-gallery__remove';
			remove.textContent = 'Remove';
			remove.dataset.index = String( index );
			row.appendChild( remove );

			container.appendChild( row );
		} );
	}

	document.addEventListener( 'click', function ( e ) {
		if ( e.target && e.target.classList && e.target.classList.contains( 'perego-client-gallery__remove' ) ) {
			e.preventDefault();
			var index = parseInt( e.target.dataset.index, 10 );
			var list = rows();
			list.splice( index, 1 );
			renderRows( list );
			return;
		}

		if ( e.target && e.target.id === 'perego-client-gallery-add' ) {
			e.preventDefault();
			if ( ! window.wp || ! window.wp.media ) { return; }
			var typeChoice = window.prompt( 'Add: type "image" for a picture, or "video" for a video URL.', 'image' );
			if ( typeChoice === 'video' ) {
				var url = window.prompt( 'Video URL (YouTube, Vimeo, or an uploaded file URL):', '' );
				if ( url ) {
					var list = rows();
					list.push( { type: 'video', id: 0, url: url } );
					renderRows( list );
				}
				return;
			}
			imageFrame = wp.media( { title: 'Add gallery image', multiple: true, library: { type: 'image' } } );
			imageFrame.on( 'select', function () {
				var selection = imageFrame.state().get( 'selection' );
				var list = rows();
				selection.forEach( function ( att ) {
					var a = att.toJSON();
					var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
					list.push( { type: 'image', id: a.id, url: url } );
				} );
				renderRows( list );
			} );
			imageFrame.open();
			return;
		}

		if ( ! e.target || e.target.id !== 'perego-client-video-select' ) { return; }
		e.preventDefault();
		if ( ! window.wp || ! window.wp.media ) { return; }
		if ( videoFrame ) { videoFrame.open(); return; }
		videoFrame = wp.media( { title: 'Select uploaded video', multiple: false, library: { type: 'video' } } );
		videoFrame.on( 'select', function () {
			var att = videoFrame.state().get( 'selection' ).first().toJSON();
			var urlField = document.getElementById( 'perego-client-video-url' );
			var typeField = document.getElementById( 'perego-client-video-type' );
			if ( urlField ) { urlField.value = att.url; }
			if ( typeField ) { typeField.value = 'upload'; }
		} );
		videoFrame.open();
	} );
}() );
JS;
    }
}
