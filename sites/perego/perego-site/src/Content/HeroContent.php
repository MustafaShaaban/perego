<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

/**
 * Resolves the homepage hero content (three slides + CTA) as an editable projection, in fallback
 * order: **block attribute → front-page post meta → seed**, per field.
 *
 * spec 020 round 4: the hero-slider block lives in the shared `front-page.html` FSE template, so —
 * matching `footer-careers`'/`ClientsCarouselRenderer`'s pattern for the same problem — RichText
 * block attributes (`slide{1,2,3}{Title,Text}{En,Ar}`, `cta{En,Ar}`) are now the primary, in-canvas-
 * editable source, checked first. The original spec 012 T004 mechanism (front-page post meta,
 * `_perego_hero_s*`, edited via a `PostMetaBoxes` sidebar box) is kept as the *second* fallback so any
 * hero copy an editor already entered there keeps rendering unchanged until re-edited via the new
 * RichText fields. The handoff copy in {@see HomeContent} is the final seed/default.
 *
 * The design is a fixed three-slide hero (matching the locked handoff).
 */
final class HeroContent
{
    public const META_SLIDE_TITLE = ['_perego_hero_s1_title', '_perego_hero_s2_title', '_perego_hero_s3_title'];
    public const META_SLIDE_TEXT  = ['_perego_hero_s1_text', '_perego_hero_s2_text', '_perego_hero_s3_text'];
    public const META_CTA         = '_perego_hero_cta';

    /**
     * Register the hero meta on the `page` post type (REST-exposed, sanitised, edit-gated). Applies to
     * pages generally; only the front page carries values and only the hero renderer reads them.
     */
    public function register(): void
    {
        $string = [
            'type'              => 'string',
            'single'            => true,
            'default'           => '',
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => [self::class, 'authEdit'],
        ];

        foreach ([...self::META_SLIDE_TITLE, ...self::META_SLIDE_TEXT, self::META_CTA] as $key) {
            register_post_meta('page', $key, $string);
        }
    }

    public static function authEdit(): bool
    {
        return function_exists('current_user_can') && current_user_can('edit_pages');
    }

    /**
     * The hero slides + CTA for the given locale: block attribute → front-page post meta → seed.
     *
     * @param array<string,mixed> $attributes
     * @return array{slides: list<array{title: string, text: string}>, cta: string}
     */
    public function resolve(int $frontPageId, string $locale, array $attributes = []): array
    {
        $home   = new HomeContent($locale);
        $seed   = $home->heroSlides();
        $cta    = $home->heroCta();
        $suffix = $locale === 'ar' ? 'Ar' : 'En';
        $hasMeta = $frontPageId > 0 && function_exists('get_post_meta');

        $slides = [];
        foreach ($seed as $index => $slide) {
            $slideNumber = $index + 1;
            $metaTitle = $hasMeta ? $this->meta($frontPageId, self::META_SLIDE_TITLE[$index] ?? '') : '';
            $metaText  = $hasMeta ? $this->meta($frontPageId, self::META_SLIDE_TEXT[$index] ?? '') : '';

            $attrTitle = trim((string) ($attributes["slide{$slideNumber}Title{$suffix}"] ?? ''));
            $attrText  = trim((string) ($attributes["slide{$slideNumber}Text{$suffix}"] ?? ''));

            $slides[] = [
                'title' => $attrTitle !== '' ? $attrTitle : ($metaTitle !== '' ? $metaTitle : $slide['title']),
                'text'  => $attrText !== '' ? $attrText : ($metaText !== '' ? $metaText : $slide['text']),
            ];
        }

        $composedSlides = $this->composedSlides($attributes, $locale, $slides);
        if ($composedSlides !== []) {
            $slides = $composedSlides;
        }

        $metaCta = $hasMeta ? $this->meta($frontPageId, self::META_CTA) : '';
        $attrCta = trim((string) ($attributes["cta{$suffix}"] ?? ''));

        return [
            'slides' => $slides,
            'cta'    => $attrCta !== '' ? $attrCta : ($metaCta !== '' ? $metaCta : $cta),
        ];
    }

    private function meta(int $postId, string $key): string
    {
        if ($key === '') {
            return '';
        }

        return (string) get_post_meta($postId, $key, true);
    }

    /** @param array<string,mixed> $attributes @param list<array{title:string,text:string}> $fallback @return list<array{title:string,text:string}> */
    private function composedSlides(array $attributes, string $locale, array $fallback): array
    {
        $source = $attributes['slides'] ?? null;
        if (! is_array($source) || $source === []) {
            return [];
        }

        $suffix = $locale === 'ar' ? 'Ar' : 'En';
        $slides = [];
        foreach (array_slice($source, 0, 6) as $index => $slide) {
            if (! is_array($slide)) {
                continue;
            }
            $title = trim((string) ($slide["title{$suffix}"] ?? ''));
            $text = trim((string) ($slide["text{$suffix}"] ?? ''));
            $fallbackSlide = $fallback[$index] ?? ['title' => '', 'text' => ''];
            if ($title !== '' || $text !== '' || $fallbackSlide['title'] !== '' || $fallbackSlide['text'] !== '') {
                $slides[] = ['title' => $title !== '' ? $title : $fallbackSlide['title'], 'text' => $text !== '' ? $text : $fallbackSlide['text']];
            }
        }

        return $slides;
    }
}
