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

        return '<header class="journal-header">'
            . '<h1 class="journal-header__title">' . esc_html($j['h1']) . '</h1>'
            . '<p class="journal-header__lead">' . esc_html($j['lead']) . '</p>'
            . '</header>';
    }
}
