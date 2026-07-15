<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Admin;

use PeregoSite\PostTypes\ProjectPostType;

defined('ABSPATH') || exit;

/**
 * A wp.media gallery picker for the Project editor (spec 014 T002 — the media UI deferred from spec
 * 010). `_perego_gallery_attachment_ids` is a protected int-array meta the front-end lightbox already
 * reads; the scalar `PostMetaBoxes` can't edit it, so this dedicated box adds the native media modal:
 * pick/replace images, see the thumbnails, clear them. The chosen ids ride in one hidden field
 * (comma-separated) and `save()` coerces them to a clean id list via the CPT's own sanitiser.
 *
 * Save is guarded by a nonce, the post capability, and autosave/revision checks — same contract as
 * PostMetaBoxes. The picker JS is inline (no build step): it uses core's already-present `wp.media`.
 */
final class ProjectGalleryMetaBox
{
    private const NONCE_ACTION = 'perego_gallery_save';
    private const NONCE_FIELD = 'perego_gallery_nonce';
    private const FIELD = ProjectPostType::META_GALLERY;

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addBox']);
        add_action('save_post', [$this, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function addBox(): void
    {
        add_meta_box(
            'perego-project-gallery',
            __('Project gallery', 'perego-site'),
            function ($post): void {
                $this->renderBox((int) $post->ID);
            },
            ProjectPostType::POST_TYPE,
            'normal',
            'default'
        );
    }

    public function enqueue(string $hook): void
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        if (get_post_type() !== ProjectPostType::POST_TYPE) {
            return;
        }

        wp_enqueue_media();
        wp_register_script('perego-gallery-picker', false, ['jquery'], null, true);
        wp_enqueue_script('perego-gallery-picker');
        wp_add_inline_script('perego-gallery-picker', $this->script());
    }

    private function renderBox(int $postId): void
    {
        $ids = ProjectPostType::sanitizeIntList(get_post_meta($postId, self::FIELD, true));

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        echo '<div class="perego-gallery-picker">';
        printf(
            '<input type="hidden" id="perego-gallery-ids" name="%1$s" value="%2$s" />',
            esc_attr(self::FIELD),
            esc_attr(implode(',', $ids))
        );
        echo '<div id="perego-gallery-preview" class="perego-gallery-picker__preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">';
        foreach ($ids as $id) {
            $thumb = wp_get_attachment_image_url($id, 'thumbnail');
            if (is_string($thumb) && $thumb !== '') {
                printf(
                    '<img src="%1$s" alt="" width="72" height="72" style="width:72px;height:72px;object-fit:cover;border-radius:6px;" />',
                    esc_url($thumb)
                );
            }
        }
        echo '</div>';
        printf(
            '<button type="button" class="button" id="perego-gallery-select">%1$s</button> '
            . '<button type="button" class="button-link" id="perego-gallery-clear">%2$s</button>',
            esc_html__('Select / edit gallery images', 'perego-site'),
            esc_html__('Clear', 'perego-site')
        );
        echo '<p class="description">' . esc_html__('These images open in the project lightbox on the front end.', 'perego-site') . '</p>';
        echo '</div>';
    }

    /**
     * @param int      $postId
     * @param \WP_Post $post
     */
    public function save($postId, $post): void
    {
        $postId = (int) $postId;
        $postType = is_object($post) ? (string) $post->post_type : (string) get_post_type($postId);
        if ($postType !== ProjectPostType::POST_TYPE) {
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
        if (! array_key_exists(self::FIELD, $_POST)) {
            return;
        }

        // The hidden field carries a comma-separated id list; explode before the CPT sanitiser (which
        // coerces each entry to a positive int).
        $raw = wp_unslash((string) $_POST[self::FIELD]);
        $ids = ProjectPostType::sanitizeIntList(explode(',', $raw));

        if ($ids === []) {
            delete_post_meta($postId, self::FIELD);
        } else {
            update_post_meta($postId, self::FIELD, $ids);
        }
    }

    /**
     * The inline picker script — opens the native wp.media modal, writes the selected ids into the
     * hidden field, and re-renders the thumbnail strip. No framework, no build; `wp.media` is enqueued
     * by wp_enqueue_media().
     */
    private function script(): string
    {
        return <<<'JS'
( function () {
    var frame;
    function ids() {
        var el = document.getElementById( 'perego-gallery-ids' );
        return el && el.value ? el.value.split( ',' ).filter( Boolean ) : [];
    }
    function render( selection ) {
        var preview = document.getElementById( 'perego-gallery-preview' );
        var hidden = document.getElementById( 'perego-gallery-ids' );
        if ( ! preview || ! hidden ) { return; }
        var picked = [];
        preview.innerHTML = '';
        selection.forEach( function ( att ) {
            var a = att.toJSON();
            picked.push( a.id );
            var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
            var img = document.createElement( 'img' );
            img.src = url; img.width = 72; img.height = 72;
            img.style.cssText = 'width:72px;height:72px;object-fit:cover;border-radius:6px;';
            preview.appendChild( img );
        } );
        hidden.value = picked.join( ',' );
    }
    document.addEventListener( 'click', function ( e ) {
        if ( e.target && e.target.id === 'perego-gallery-clear' ) {
            e.preventDefault();
            var preview = document.getElementById( 'perego-gallery-preview' );
            var hidden = document.getElementById( 'perego-gallery-ids' );
            if ( preview ) { preview.innerHTML = ''; }
            if ( hidden ) { hidden.value = ''; }
            return;
        }
        if ( ! e.target || e.target.id !== 'perego-gallery-select' ) { return; }
        e.preventDefault();
        if ( ! window.wp || ! window.wp.media ) { return; }
        if ( frame ) { frame.open(); return; }
        frame = wp.media( { title: 'Project gallery', multiple: true, library: { type: 'image' } } );
        frame.on( 'open', function () {
            var sel = frame.state().get( 'selection' );
            ids().forEach( function ( id ) {
                var att = wp.media.attachment( id );
                att.fetch();
                sel.add( att );
            } );
        } );
        frame.on( 'select', function () {
            render( frame.state().get( 'selection' ) );
        } );
        frame.open();
    } );
}() );
JS;
    }
}
