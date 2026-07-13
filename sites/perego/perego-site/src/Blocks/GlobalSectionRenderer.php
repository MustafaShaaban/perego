<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\GlobalSectionResolver;
use PeregoSite\PostTypes\GlobalSectionPostType;

/**
 * Server-renders the `perego/global-section` block (spec Phase 5). Given a role attribute chosen in
 * the sidebar (a structural setting, not prose), it resolves the linked `perego_global_section`
 * record for the current Polylang language and renders that record's canvas-authored block content.
 *
 * The no-mixing rule is absolute: if the current language has no record for the role, a visitor sees
 * nothing (never the other language's copy), while an editor sees an inline notice so the missing
 * translation is visible in the authoring environment — matching the tested resolver contract.
 */
final class GlobalSectionRenderer
{
    public function __construct(
        private readonly string $locale,
        private readonly GlobalSectionResolver $resolver,
    ) {
    }

    /** @param array<string,mixed> $attributes */
    public function render(array $attributes): string
    {
        $role = sanitize_key((string) ($attributes['role'] ?? ''));
        if ($role === '') {
            return '';
        }

        $id = $this->resolver->resolve($role, $this->locale, $this->candidates());
        if ($id === null) {
            return $this->missingNotice($role);
        }

        return $this->renderRecord($id);
    }

    /** @return list<array{id:int,role:string,locale:string}> */
    private function candidates(): array
    {
        $ids = get_posts([
            'post_type' => GlobalSectionPostType::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 100,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => false,
        ]);

        $candidates = [];
        foreach ((array) $ids as $id) {
            $id = (int) $id;
            $candidates[] = [
                'id' => $id,
                'role' => (string) get_post_meta($id, GlobalSectionPostType::META_ROLE, true),
                'locale' => $this->postLocale($id),
            ];
        }

        return $candidates;
    }

    private function postLocale(int $id): string
    {
        if (function_exists('pll_get_post_language')) {
            $locale = (string) pll_get_post_language($id);

            return $locale === '' ? 'en' : $locale;
        }

        return 'en';
    }

    /**
     * The record's post_content is editor-authored block markup, sanitized by WordPress on save;
     * do_blocks is the standard the_content render path for stored blocks.
     */
    private function renderRecord(int $id): string
    {
        $post = get_post($id);
        if ($post === null) {
            return '';
        }

        return do_blocks($post->post_content);
    }

    private function missingNotice(string $role): string
    {
        if (! function_exists('current_user_can') || ! current_user_can('edit_posts')) {
            return '';
        }

        $message = sprintf(
            /* translators: 1: section role key, 2: language locale */
            __('Missing “%1$s” global section for the “%2$s” language. Add and link a translation.', 'perego-site'),
            $role,
            $this->locale
        );

        return '<div class="perego-global-section perego-global-section--missing" role="note">'
            . esc_html($message)
            . '</div>';
    }
}
