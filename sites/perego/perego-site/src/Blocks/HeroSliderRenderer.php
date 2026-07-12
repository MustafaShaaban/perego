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
 * Server-renders the perego/hero-slider block (spec 002 / M2, US1): a full-bleed hero with a
 * rotating set of headline slides, a dot tablist, prev/next and pause/play controls, an aria-live
 * announcer, and the "Say Hello!" CTA. All motion/state (auto-advance, hover + visibility pause,
 * reduced-motion gate, stop-on-interaction) is wired by view.js through the Interactivity API
 * directives emitted here — this class only emits markup + the initial (pre-hydration) state.
 *
 * Ported from the handoff prototype's `.hero` section (site/index.html + main.js heroSlider), with
 * the ACCESSIBILITY_HANDOFF build additions (prev/next, pause/play, live region, stop-on-interaction).
 */
final class HeroSliderRenderer
{
    public function __construct(private readonly LanguageService $languageService)
    {
    }

    public function render(): string
    {
        $content = new HomeContent($this->languageService->driver()->currentLocale());
        $slides  = $content->heroSlides();

        $context = esc_attr((string) wp_json_encode([
            'activeIndex' => 0,
            'isPlaying'   => true,
            'count'       => count($slides),
            // Localized strings; view.js substitutes/derives from these so translation happens
            // server-side where `__()` is available. `announce` is a template (slide X of Y).
            'announce'    => __('Slide %1$s of %2$s', 'perego-site'),
            'pauseLabel'  => __('Pause slideshow', 'perego-site'),
            'resumeLabel' => __('Play slideshow', 'perego-site'),
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

        $html .= '<div class="hero__inner">';
        $html .= '<div class="hero__content">';
        $html .= $this->renderSlides($slides);
        $html .= '<div class="hero__cta">'
            . '<a class="perego-btn perego-btn--accent" href="' . esc_url(home_url('/contact')) . '">'
            . esc_html($content->heroCta()) . '</a>'
            . '</div>';
        $html .= '</div>'; // .hero__content
        $html .= '</div>'; // .hero__inner

        $html .= $this->renderControls();
        $html .= $this->renderDots(count($slides));

        // Polite live region: announces the current slide for assistive tech (build note, not in the
        // static prototype). Text is bound to the store's derived label.
        $html .= '<p class="hero__status screen-reader-text" aria-live="polite" '
            . 'data-wp-text="state.currentSlideLabel"></p>';

        $html .= '</section>';

        return $html;
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
            $html .= '<' . $titleTag . ' class="hero__title">' . esc_html($slide['title']) . '</' . $titleTag . '>';
            $html .= '<p class="hero__text">' . esc_html($slide['text']) . '</p>';
            $html .= '</div>';
        }

        return $html;
    }

    private function renderControls(): string
    {
        $html = '<div class="hero__controls">';

        $html .= '<button type="button" class="hero__control hero__control--prev" '
            . 'aria-label="' . esc_attr__('Previous slide', 'perego-site') . '" '
            . 'data-wp-on--click="actions.prev"></button>';

        $html .= '<button type="button" class="hero__control hero__control--play" '
            . 'aria-label="' . esc_attr__('Pause slideshow', 'perego-site') . '" '
            . 'aria-pressed="false" '
            . 'data-wp-bind--aria-pressed="callbacks.playPressed" '
            . 'data-wp-bind--aria-label="state.playLabel" '
            . 'data-wp-on--click="actions.togglePlay"></button>';

        $html .= '<button type="button" class="hero__control hero__control--next" '
            . 'aria-label="' . esc_attr__('Next slide', 'perego-site') . '" '
            . 'data-wp-on--click="actions.next"></button>';

        $html .= '</div>';

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
