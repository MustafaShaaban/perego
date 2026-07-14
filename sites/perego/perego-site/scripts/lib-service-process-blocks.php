<?php

/**
 * Shared block-builder for "Our Process" — used by both `seed-services.php` (fresh seeds) and
 * `migrate-service-process.php` (retrofitting already-seeded posts), so the two never drift. A
 * plain include with no top-level side effects: safe to `require` from either script.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\ServiceContent;

if (! function_exists('buildProcessBlocks')) {
    /**
     * The handoff's designed icon/card/arrow process contract (matching the Services archive's
     * ServicesOverviewRenderer::renderProcess()), built from real editable core blocks so each step's
     * label/desc stays canvas-editable — only the icon and the decorative arrows between steps are
     * structural (wp:html), consistent with the editor-canvas rule (prose stays in RichText-backed
     * blocks; only non-editorial iconography sits outside it).
     */
    function buildProcessBlocks(ServiceContent $content, string $slug): string
    {
        // Icon file, in the handoff's fixed step order (mirrors ServicesOverviewRenderer::PROCESS_ICONS).
        $icons = ['icon-clapper.png', 'icon-film-l.png', 'icon-star.png', 'icon-film-h.png'];
        $arrowSvg = '<svg viewBox="0 0 24 24"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" '
            . 'stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $iconBase = get_stylesheet_directory_uri() . '/assets/images/';
        $steps = $content->processSteps($slug);
        $lastIndex = count($steps) - 1;

        $blocks = '<!-- wp:group {"align":"full","className":"process","layout":{"type":"constrained"}} -->' . "\n";
        $blocks .= '<section class="wp-block-group alignfull process">' . "\n";
        $blocks .= '<!-- wp:heading {"className":"section-title"} --><h2 class="wp-block-heading section-title">'
            . esc_html($content->label('ourProcess')) . '</h2><!-- /wp:heading -->' . "\n";
        $blocks .= '<!-- wp:group {"className":"process-list","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->' . "\n";
        $blocks .= '<div class="wp-block-group process-list">' . "\n";

        foreach ($steps as $index => $step) {
            $icon = $icons[$index] ?? $icons[0];
            $blocks .= '<!-- wp:group {"className":"process-step","layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->' . "\n";
            $blocks .= '<div class="wp-block-group process-step">' . "\n";
            $blocks .= '<!-- wp:image {"className":"process-step__icon","sizeSlug":"thumbnail"} -->'
                . '<figure class="wp-block-image size-thumbnail process-step__icon">'
                . '<img src="' . esc_url($iconBase . $icon) . '" alt=""/></figure><!-- /wp:image -->' . "\n";
            $blocks .= '<!-- wp:paragraph {"className":"process-step__label"} --><p class="process-step__label"><strong>'
                . esc_html($step['label']) . '</strong></p><!-- /wp:paragraph -->' . "\n";
            $blocks .= '<!-- wp:paragraph {"className":"process-step__desc"} --><p class="process-step__desc">'
                . esc_html($step['desc']) . '</p><!-- /wp:paragraph -->' . "\n";
            $blocks .= '</div>' . "\n" . '<!-- /wp:group -->' . "\n";

            if ($index !== $lastIndex) {
                $blocks .= '<!-- wp:html --><span class="process-arrow" aria-hidden="true">' . $arrowSvg . '</span><!-- /wp:html -->' . "\n";
            }
        }

        $blocks .= '</div>' . "\n" . '<!-- /wp:group -->' . "\n";
        $blocks .= '</section>' . "\n";
        $blocks .= '<!-- /wp:group -->' . "\n";

        return $blocks;
    }
}
