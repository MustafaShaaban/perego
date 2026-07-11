<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\GlobalContent;

/**
 * Server-renders the perego-theme/not-found block (M4): the 404 composition — a large "404" code,
 * the title as the page H1, the body copy, and the Back-to-Home + Contact actions. Language-aware
 * via GlobalContent (the language-neutral 404 template can't hold EN/AR prose under Polylang Free).
 * Ported from the handoff `404.html`.
 */
final class NotFoundRenderer
{
    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(): string
    {
        $c = $this->content->notFound();

        $html = '<section class="notfound" aria-labelledby="notfound-title">';
        $html .= '<p class="notfound__code" aria-hidden="true">' . esc_html($c['code']) . '</p>';
        $html .= '<h1 class="notfound__title" id="notfound-title">' . esc_html($c['title']) . '</h1>';
        $html .= '<p class="notfound__text">' . esc_html($c['text']) . '</p>';
        $html .= '<div class="notfound__actions">';
        $html .= '<a class="btn btn--accent" href="' . esc_url(home_url('/')) . '">' . esc_html($c['backHome']) . '</a>';
        $html .= '<a class="btn btn--dark" href="' . esc_url(home_url('/contact')) . '">' . esc_html($c['contact']) . '</a>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }
}
