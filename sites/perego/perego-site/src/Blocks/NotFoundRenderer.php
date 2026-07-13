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

        $html = '<main class="error-page">';
        $html .= '<a class="logo error-logo" href="' . esc_url(home_url('/')) . '" aria-label="' . esc_attr(__('Perego — home', 'perego-site')) . '">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/logo-full.png') . '" alt="' . esc_attr(__('Perego بيريجو', 'perego-site')) . '" /></a>';
        $html .= '<div class="error-page__bg" aria-hidden="true"><img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/hero-bg.png') . '" alt="" /></div>';
        $html .= '<div class="error-grid" aria-hidden="true"></div><div class="error-orbs" aria-hidden="true">'
            . '<span class="orb orb--1"></span><span class="orb orb--2"></span><span class="orb orb--3"></span><span class="orb orb--4"></span></div>';
        $html .= '<div class="error-page__inner" id="main" tabindex="-1">';
        $html .= '<p class="error-code" aria-hidden="true">' . esc_html($c['code']) . '</p>';
        $html .= '<h1 class="error-title">' . esc_html($c['title']) . '</h1>';
        $html .= '<p class="error-text">' . esc_html($c['text']) . '</p><div class="error-actions">';
        $html .= '<a class="btn btn--accent" href="' . esc_url(home_url('/')) . '">' . esc_html($c['backHome']) . '</a>';
        $html .= '<a class="btn btn--dark" href="' . esc_url(home_url('/contact')) . '">' . esc_html($c['contact']) . '</a></div></div></main>';

        return $html;
    }
}
