<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\HeroContent;
use PeregoSite\Services\LanguageService;
use PeregoSite\Theme\SiteRoutes;

/**
 * Server-renders the perego/hero-slider block (spec 002 / M2, US1): a full-bleed hero with a
 * rotating set of headline slides, a dot tablist, an aria-live announcer, and the "Say Hello!" CTA.
 * All motion/state (auto-advance, hover + visibility pause, reduced-motion gate, stop-on-interaction)
 * is wired by view.js through the Interactivity API directives emitted here — this class only emits
 * markup + the initial (pre-hydration) state.
 *
 * Ported from the handoff prototype's `.hero` section (site/index.html + main.js heroSlider). The
 * handoff's ACCESSIBILITY_HANDOFF build note also asks for prev/next + pause/play buttons; the owner
 * explicitly asked for those removed (dots-only) — see DECISIONS.md — so only the live region and
 * stop-on-interaction behavior from that note were kept.
 */
final class HeroSliderRenderer
{
    public function __construct(private readonly LanguageService $languageService)
    {
    }

    /** @param array<string,string> $attributes */
    public function render(array $attributes = []): string
    {
        $locale = $this->languageService->driver()->currentLocale();
        $hero   = (new HeroContent())->resolve($this->frontPageId(), $locale, $attributes);
        $slides = $hero['slides'];

        $context = esc_attr((string) wp_json_encode([
            'activeIndex' => 0,
            'isPlaying'   => true,
            'count'       => count($slides),
            // Localized string; view.js substitutes into it so translation happens server-side
            // where `__()` is available. `announce` is a template (slide X of Y).
            /* translators: 1: current slide number, 2: total number of slides. */
            'announce'    => __('Slide %1$s of %2$s', 'perego-site'),
        ]));

        $html = '<section class="hero" id="hero" aria-label="' . esc_attr__('Introduction', 'perego-site') . '" '
            . 'data-wp-interactive="perego/hero-slider" '
            . "data-wp-context='" . $context . "' "
            . 'data-wp-on--mouseenter="actions.pause" '
            . 'data-wp-on--mouseleave="actions.resume" '
            . 'data-wp-init="callbacks.init">';

        $html .= '<div class="hero__prism" aria-hidden="true">'
            . '<img src="' . esc_url(get_stylesheet_directory_uri() . '/assets/images/hero-bg.png') . '" alt="" />'
            . '</div>';

        // Swipe/drag navigation (view.js actions.pointerDown/Up): pointer events cover touch + mouse.
        $html .= '<div class="container hero__inner" '
            . 'data-wp-on--pointerdown="actions.pointerDown" '
            . 'data-wp-on--pointerup="actions.pointerUp">';
        $html .= '<div class="hero__content hero-enter">';
        $html .= $this->renderSlides($slides);
        // spec 021 T036: the CTA may name a page instead of the default contact route.
        $cta = LinkTarget::fromAttributes($attributes);
        $linkTarget = new LinkTarget($this->languageService->driver());
        $html .= '<div class="hero__cta">'
            . '<a class="btn btn--accent" href="' . esc_url($linkTarget->href($cta, SiteRoutes::START_PROJECT)) . '"'
            . $linkTarget->targetAttributes($cta) . '>'
            . wp_kses_post($hero['cta']) . '</a>'
            . '</div>';
        $html .= '</div>'; // .hero__content
        $html .= '</div>'; // .hero__inner

        $html .= $this->renderDots(count($slides));

        // Polite live region: announces the current slide for assistive tech (build note, not in the
        // static prototype). Text is bound to the store's derived label.
        $html .= '<p class="hero__status screen-reader-text" aria-live="polite" '
            . 'data-wp-text="state.currentSlideLabel"></p>';

        $html .= '</section>';

        return $html;
    }

    /**
     * The page whose hero meta to read. On the static front page the queried object *is* that page
     * (EN or AR), giving locale-correct overrides; otherwise fall back to the configured front page.
     */
    private function frontPageId(): int
    {
        $queried = function_exists('get_queried_object_id') ? (int) get_queried_object_id() : 0;
        if ($queried > 0) {
            return $queried;
        }

        return function_exists('get_option') ? (int) get_option('page_on_front') : 0;
    }

    /**
     * @param list<array{title: string, text: string}> $slides
     */
    private function renderSlides(array $slides): string
    {
        $html = '';

        foreach ($slides as $index => $slide) {
            $isFirst = $index === 0;
            // Pre-hydration: only the first slide is visible; view.js takes over via data-wp-bind--hidden.
            $hiddenAttr = $isFirst ? '' : ' hidden';
            $titleTag   = $isFirst ? 'h1' : 'p';

            $html .= '<div class="hero__slide" data-slide="' . esc_attr((string) $index) . '" '
                . "data-wp-context='" . esc_attr((string) wp_json_encode(['index' => $index])) . "' "
                . 'data-wp-bind--hidden="callbacks.slideHidden"' . $hiddenAttr . '>';
            $html .= '<' . $titleTag . ' class="hero__title">' . wp_kses_post($slide['title']) . '</' . $titleTag . '>';
            $html .= '<p class="hero__text">' . wp_kses_post($slide['text']) . '</p>';
            $html .= '</div>';
        }

        return $html;
    }

    private function renderDots(int $count): string
    {
        $html = '<div class="hero__dots" role="tablist" aria-label="' . esc_attr__('Hero slides', 'perego-site') . '">';

        for ($index = 0; $index < $count; $index++) {
            $isFirst = $index === 0;

            $html .= '<button type="button" class="hero__dot' . ($isFirst ? ' is-active' : '') . '" '
                . 'role="tab" '
                . 'aria-label="' . esc_attr(sprintf(/* translators: %d: slide number */ __('Slide %d', 'perego-site'), $index + 1)) . '" '
                . 'aria-selected="' . ($isFirst ? 'true' : 'false') . '" '
                . "data-wp-context='" . esc_attr((string) wp_json_encode(['index' => $index])) . "' "
                . 'data-wp-bind--aria-selected="callbacks.dotSelected" '
                . 'data-wp-class--is-active="callbacks.dotSelected" '
                . 'data-wp-on--click="actions.goTo"></button>';
        }

        $html .= '</div>';

        return $html;
    }
}
