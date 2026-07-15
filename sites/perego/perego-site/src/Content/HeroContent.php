<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

/**
 * Resolves the homepage hero content (three slides + CTA) as an editable projection of the front-page
 * page (spec 012 T004). The handoff copy in {@see HomeContent} is the seed/default; each language's
 * front page (a real translated `page`, e.g. EN 42 / AR 97) can override any field via post meta, so
 * the hero edits per locale in the block/page editor. Missing or empty meta falls back to the seed
 * PER FIELD, so output stays byte-identical until an editor sets a value.
 *
 * The design is a fixed three-slide hero (matching the locked handoff), so the meta is three
 * scalar title/text pairs plus the CTA — simple to register, sanitise, edit and seed.
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
     * The hero slides + CTA for the given locale, overlaying the front page's meta on the seed.
     *
     * @return array{slides: list<array{title: string, text: string}>, cta: string}
     */
    public function resolve(int $frontPageId, string $locale): array
    {
        $home  = new HomeContent($locale);
        $seed  = $home->heroSlides();
        $cta   = $home->heroCta();

        if ($frontPageId <= 0 || ! function_exists('get_post_meta')) {
            return ['slides' => $seed, 'cta' => $cta];
        }

        $slides = [];
        foreach ($seed as $index => $slide) {
            $title = $this->meta($frontPageId, self::META_SLIDE_TITLE[$index] ?? '');
            $text  = $this->meta($frontPageId, self::META_SLIDE_TEXT[$index] ?? '');

            $slides[] = [
                'title' => $title !== '' ? $title : $slide['title'],
                'text'  => $text !== '' ? $text : $slide['text'],
            ];
        }

        $metaCta = $this->meta($frontPageId, self::META_CTA);

        return [
            'slides' => $slides,
            'cta'    => $metaCta !== '' ? $metaCta : $cta,
        ];
    }

    private function meta(int $postId, string $key): string
    {
        if ($key === '') {
            return '';
        }

        return (string) get_post_meta($postId, $key, true);
    }
}
