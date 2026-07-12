<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\HomeContent;
use PeregoSite\Services\LanguageService;

/**
 * Server-renders the perego/home-about block (spec 004 T010): the homepage "About Us" / "Our
 * mission" section — a full-bleed hooded-figure background image and two glass panels. Ported from
 * the handoff prototype's `.home-about` section. Locale-aware via HomeContent so the Arabic route
 * renders Arabic copy (previously hardcoded English in front-page.html). Static (no view script).
 */
final class HomeAboutRenderer
{
    public function __construct(private readonly LanguageService $languageService)
    {
    }

    public function render(): string
    {
        $content = new HomeContent($this->languageService->driver()->currentLocale());
        $about   = $content->about();

        $html = '<section class="home-about" id="about" aria-labelledby="home-about-title">';

        $html .= '<div class="home-about__bg" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/about-hooded.png') . '" alt="" loading="lazy" />'
            . '</div>';

        $html .= '<div class="home-about__inner">';
        $html .= '<div class="home-about__panels">';

        $html .= '<article class="glass-panel">'
            . '<h2 class="panel-title" id="home-about-title">' . esc_html($about['title']) . '</h2>'
            . '<p>' . esc_html($about['body']) . '</p>'
            . '</article>';

        $html .= '<article class="glass-panel">'
            . '<h2 class="panel-title">' . esc_html($about['missionTitle']) . '</h2>'
            . '<p>' . esc_html($about['missionBody']) . '</p>'
            . '</article>';

        $html .= '</div>'; // .home-about__panels
        $html .= '</div>'; // .home-about__inner
        $html .= '</section>';

        return $html;
    }
}
