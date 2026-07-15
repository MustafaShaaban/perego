<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego-theme/footer-careers block: the footer "Join us" careers heading + blurb.
 * The EN and AR variants both live in the block's attributes; this emits only the current Polylang
 * language. It reconstructs the exact core heading/paragraph markup the (now-removed) footer-careers
 * Global Section stored and runs it through do_blocks, so the rendered output is byte-identical to the
 * previous CPT-backed render while the content is now edited on the block itself.
 */
final class FooterCareersRenderer
{
    public function __construct(private readonly string $locale)
    {
    }

    /** @param array<string,mixed> $attributes */
    public function render(array $attributes): string
    {
        $isArabic = $this->locale === 'ar';

        $heading = wp_kses_post((string) ($attributes[$isArabic ? 'headingAr' : 'headingEn'] ?? ''));
        $blurb = wp_kses_post((string) ($attributes[$isArabic ? 'blurbAr' : 'blurbEn'] ?? ''));

        if ($heading === '' && $blurb === '') {
            return '';
        }

        // Same core-block markup the footer-careers record held, so do_blocks yields identical output
        // (the wp-block-* classes and typographic punctuation are applied by the core render path).
        $markup = '<!-- wp:heading {"level":2,"className":"footer-heading"} -->'
            . '<h2 class="wp-block-heading footer-heading">' . $heading . '</h2>'
            . '<!-- /wp:heading -->'
            . '<!-- wp:paragraph {"className":"footer-blurb"} -->'
            . '<p class="footer-blurb">' . $blurb . '</p>'
            . '<!-- /wp:paragraph -->';

        return do_blocks($markup);
    }
}
