<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Admin;

use PeregoSite\Blocks\LegalUpdatedRenderer;
use PeregoSite\PostTypes\ClientPostType;
use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;

defined('ABSPATH') || exit;

/**
 * Editor controls for the structured Project/Service/Client metadata (spec 010 T009). The fields are
 * registered as real post meta (see each PostType::metaArgs); this adds a labelled meta box so an editor
 * can fill them in wp-admin — no CLI, no seed script, and not the generic Custom Fields panel (protected
 * `_`-prefixed keys never appear there). Scalar text fields only; the Project gallery and the Client
 * gallery/video source (a repeater + a `wp.media` picker) keep their own dedicated media-UI boxes
 * (`ProjectGalleryMetaBox`, `ClientMediaMetaBox`). Saving is guarded by a nonce, the post-type
 * capability, and autosave/revision checks, and each value is sanitised with the same callback its meta
 * registration declares.
 */
final class PostMetaBoxes
{
    private const NONCE_ACTION = 'perego_meta_save';
    private const NONCE_FIELD = 'perego_meta_nonce';

    /**
     * post type => [ box title, [ meta key => [label, sanitizer] ] ]. Scalar fields only.
     *
     * @return array<string, array{title:string, fields:array<string, array{label:string, sanitize:callable-string}>}>
     */
    public function schema(): array
    {
        return [
            ProjectPostType::POST_TYPE => [
                'title' => __('Project details', 'perego-site'),
                'fields' => [
                    ProjectPostType::META_CLIENT => ['label' => __('Client', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    ProjectPostType::META_YEAR => ['label' => __('Year', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    ProjectPostType::META_ROLE => ['label' => __('Role', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    ProjectPostType::META_DELIVERABLES => ['label' => __('Deliverables', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    // Website-showcase card data (web-category projects only; spec 020 service parity).
                    ProjectPostType::META_SITE_TYPE => ['label' => __('Site type — ecommerce / corporate / landing / webapp / portfolio (web projects)', 'perego-site'), 'sanitize' => ProjectPostType::class . '::sanitizeSiteType'],
                    ProjectPostType::META_SITE_URL => ['label' => __('Live site URL (web projects)', 'perego-site'), 'sanitize' => 'esc_url_raw'],
                    ProjectPostType::META_VIDEO_URL => ['label' => __('Video URL (YouTube/Vimeo or video file) — the project opens as a ▶ video card', 'perego-site'), 'sanitize' => 'esc_url_raw'],
                ],
            ],
            ServicePostType::POST_TYPE => [
                'title' => __('Service details', 'perego-site'),
                'fields' => [
                    ServicePostType::META_SERVICE_SLUG => ['label' => __('Canonical service key (video/motion/design/web)', 'perego-site'), 'sanitize' => 'sanitize_key'],
                    ServicePostType::META_TEASER_LABEL => ['label' => __('Homepage card label (e.g. "Video Editing")', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    ServicePostType::META_TEASER_IMAGE_ID => ['label' => __('Homepage card image (attachment ID)', 'perego-site'), 'sanitize' => 'absint'],
                    ServicePostType::META_TEASER_ALT => ['label' => __('Homepage card image alt text', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                ],
            ],
            ClientPostType::POST_TYPE => [
                'title' => __('Client details', 'perego-site'),
                'fields' => [
                    ClientPostType::META_SUB => ['label' => __('Subtitle — individual card only (e.g. "intertainment show")', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    ClientPostType::META_STAT => ['label' => __('Statistic — individual card (wrap the number in <strong> to bold it, e.g. <strong>+1M</strong> views)', 'perego-site'), 'sanitize' => ClientPostType::class . '::sanitizeStat'],
                ],
            ],
            // spec 017 — the legal pages' curated "Last updated" date. Lives on the `page` type but the
            // box is scoped to legal-template pages in addBoxes(); empty falls back to the modified date
            // in LegalUpdatedRenderer.
            'page:legal' => [
                'title' => __('Legal page', 'perego-site'),
                'fields' => [
                    LegalUpdatedRenderer::META_UPDATED => ['label' => __('Last updated (e.g. 2026-07-01) — blank uses the modified date', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                ],
            ],
        ];
    }

    /** The real WordPress post type for a schema key (`page:legal` → `page`). */
    private function realType(string $schemaKey): string
    {
        $colon = strpos($schemaKey, ':');

        return $colon === false ? $schemaKey : substr($schemaKey, 0, $colon);
    }

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addBoxes'], 10, 2);
        add_action('save_post', [$this, 'save'], 10, 2);
    }

    /**
     * @param string        $postType the screen's post type (from the add_meta_boxes hook)
     * @param \WP_Post|null $post     the post being edited (from the add_meta_boxes hook)
     */
    public function addBoxes($postType = '', $post = null): void
    {
        foreach ($this->schema() as $type => $box) {
            // The legal box lives on the `page` type but only applies to legal-template pages.
            if ($type === 'page:legal' && ! $this->isLegalPage($post)) {
                continue;
            }

            add_meta_box(
                'perego-meta-' . sanitize_key($type),
                $box['title'],
                function ($boxPost) use ($type): void {
                    $this->renderBox($type, (int) $boxPost->ID);
                },
                $this->realType($type),
                'normal',
                'default'
            );
        }
    }

    /** Is the given post a legal page (the `legal` page template)? The "Last updated" meta only applies there. */
    private function isLegalPage($post): bool
    {
        if (! is_object($post) || ! isset($post->ID)) {
            return false;
        }

        return get_page_template_slug((int) $post->ID) === 'legal';
    }

    private function renderBox(string $postType, int $postId): void
    {
        $fields = $this->schema()[$postType]['fields'] ?? [];

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        echo '<div class="perego-meta-box">';
        foreach ($fields as $key => $field) {
            $value = (string) get_post_meta($postId, $key, true);
            $id = 'perego-meta-' . sanitize_key($key);
            printf(
                '<p><label for="%1$s" style="display:block;font-weight:600;margin-bottom:4px;">%2$s</label>'
                . '<input type="text" id="%1$s" name="%3$s" value="%4$s" class="widefat" /></p>',
                esc_attr($id),
                esc_html($field['label']),
                esc_attr($key),
                esc_attr($value)
            );
        }
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

        // Every schema entry whose real post type matches this save (e.g. both `page` and `page:legal`
        // apply to a `page`).
        $boxes = array_filter(
            $this->schema(),
            fn (string $type): bool => $this->realType($type) === $postType,
            ARRAY_FILTER_USE_KEY
        );
        if ($boxes === []) {
            return;
        }

        // Guards: autosave/revision, nonce, capability.
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

        foreach ($boxes as $box) {
            foreach ($box['fields'] as $key => $field) {
                if (! array_key_exists($key, $_POST)) {
                    continue;
                }
                $raw = wp_unslash((string) $_POST[$key]);
                $clean = call_user_func($field['sanitize'], $raw);
                if ($clean === '') {
                    delete_post_meta($postId, $key);
                } else {
                    update_post_meta($postId, $key, $clean);
                }
            }
        }
    }
}
