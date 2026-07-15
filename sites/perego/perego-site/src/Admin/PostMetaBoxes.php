<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Admin;

use PeregoSite\Content\HeroContent;
use PeregoSite\PostTypes\ClientPostType;
use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;

defined('ABSPATH') || exit;

/**
 * Editor controls for the structured Project/Service/Client metadata (spec 010 T009). The fields are
 * registered as real post meta (see each PostType::metaArgs); this adds a labelled meta box so an editor
 * can fill them in wp-admin — no CLI, no seed script, and not the generic Custom Fields panel (protected
 * `_`-prefixed keys never appear there). Scalar text fields only; the Project gallery keeps its own media
 * UI follow-up. Saving is guarded by a nonce, the post-type capability, and autosave/revision checks, and
 * each value is sanitised with the same callback its meta registration declares.
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
                    ClientPostType::META_STAT => ['label' => __('Statistic', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    ClientPostType::META_VIDEO_URL => ['label' => __('Video URL', 'perego-site'), 'sanitize' => 'esc_url_raw'],
                ],
            ],
            // spec 012 T004 — the homepage hero, editable per language on the front page (the box is
            // scoped to the front page in addBoxes(); other pages never show it). Empty fields fall back
            // to the HomeContent seed in HeroContent::resolve().
            'page' => [
                'title' => __('Homepage hero', 'perego-site'),
                'fields' => [
                    HeroContent::META_SLIDE_TITLE[0] => ['label' => __('Slide 1 title', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    HeroContent::META_SLIDE_TEXT[0] => ['label' => __('Slide 1 text', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    HeroContent::META_SLIDE_TITLE[1] => ['label' => __('Slide 2 title', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    HeroContent::META_SLIDE_TEXT[1] => ['label' => __('Slide 2 text', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    HeroContent::META_SLIDE_TITLE[2] => ['label' => __('Slide 3 title', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    HeroContent::META_SLIDE_TEXT[2] => ['label' => __('Slide 3 text', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                    HeroContent::META_CTA => ['label' => __('CTA button label', 'perego-site'), 'sanitize' => 'sanitize_text_field'],
                ],
            ],
        ];
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
            // The hero box lives on the `page` type but is only relevant to the front page (and its
            // translations) — never every page.
            if ($type === 'page' && ! $this->isFrontPage($post)) {
                continue;
            }

            add_meta_box(
                'perego-meta-' . $type,
                $box['title'],
                function ($boxPost) use ($type): void {
                    $this->renderBox($type, (int) $boxPost->ID);
                },
                $type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Is the given post the site's front page (or one of its Polylang translations)? The hero meta
     * only applies there.
     */
    private function isFrontPage($post): bool
    {
        if (! is_object($post) || ! isset($post->ID)) {
            return false;
        }

        $frontId = (int) get_option('page_on_front');
        if ($frontId <= 0) {
            return false;
        }

        $ids = [$frontId];
        if (function_exists('pll_get_post_translations')) {
            $ids = array_map('intval', array_values(pll_get_post_translations($frontId))) ?: $ids;
        }

        return in_array((int) $post->ID, $ids, true);
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

        $schema = $this->schema();
        if (! isset($schema[$postType])) {
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

        foreach ($schema[$postType]['fields'] as $key => $field) {
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
