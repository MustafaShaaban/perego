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
    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(): string
    {
        $j = $this->content->journal();

        $html = '<header class="journal-header">';
        $html .= '<nav class="page-crumb" aria-label="' . esc_attr__('Breadcrumb', 'perego-site') . '">';
        $html .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($this->content->uiHome()) . '</a>';
        $html .= '<span aria-hidden="true">/</span>';
        $html .= '<span aria-current="page">' . esc_html($j['h1']) . '</span>';
        $html .= '</nav>';
        $html .= '<h1 class="journal-header__title">' . esc_html($j['h1']) . '</h1>';
        $html .= '<p class="journal-header__lead">' . esc_html($j['lead']) . '</p>';
        $html .= '</header>';

        return $html;
    }
}
