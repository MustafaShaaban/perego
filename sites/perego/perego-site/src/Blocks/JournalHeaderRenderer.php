<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\GlobalContent;

/**
 * Server-renders the perego-theme/journal-header block (M5): the journal index heading — "The Perego
 * Journal" (page H1) + the lead line. Language-aware via GlobalContent; sits above the core Query
 * Loop of posts in the blog-index template. Ported from the handoff `archive.html`.
 */
final class JournalHeaderRenderer
{
    /**
     * @param array{title?: string, lead?: string} $overrides the block's editable copy, already
     *        resolved to the current locale by `LocalizedAttributes::pick` (spec 021 C12)
     */
    public function __construct(
        private readonly GlobalContent $content,
        private readonly array $overrides = [],
    ) {
    }

    public function render(): string
    {
        // The Content class owns the seed-vs-override rule; only a non-empty override wins, so an
        // unedited block renders exactly what it always did.
        $j = $this->content->journal([
            'h1' => $this->overrides['title'] ?? null,
            'lead' => $this->overrides['lead'] ?? null,
        ]);

        $html = '<div class="post-hero__inner" style="text-align:center;">';
        $html .= '<nav class="page-crumb" style="justify-content:center;" aria-label="' . esc_attr__('Breadcrumb', 'perego-site') . '">';
        $html .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($this->content->uiHome()) . '</a>';
        $html .= '<span aria-hidden="true">/</span>';
        $html .= '<span aria-current="page">' . esc_html($j['h1']) . '</span>';
        $html .= '</nav>';
        $html .= '<h1 class="post-title" style="font-size:clamp(34px,4.5vw,60px);margin-top:14px;">' . esc_html($j['h1']) . '</h1>';
        $html .= '<p class="section-lead">' . esc_html($j['lead']) . '</p>';
        $html .= '</div>';

        return $html;
    }
}
