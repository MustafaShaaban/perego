<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Seo;

defined('ABSPATH') || exit;

/**
 * Language-aware meta descriptions + Open Graph / Twitter tags (spec Phase 10). WordPress core emits
 * the <title>, but not a description — this adds a dynamic one on every route: a singular entity uses
 * its own excerpt; every other route (front page, archives, search, 404) uses the localized brand
 * description (the factual EN/AR list of the four services — no invented claims). The pure `describe`
 * and `brandDescription` helpers are unit-tested; `render` is thin wp_head glue.
 */
final class PeregoMeta
{
    private const MAX_LENGTH = 160;

    /** @var array<string,string> */
    private const BRAND = [
        'en' => 'Perego Creative Studio — video editing, 2D motion graphics, graphic design and website making.',
        'ar' => 'بيريجو استوديو إبداعي — مونتاج فيديو، وموشن جرافيك ثنائي الأبعاد، وتصميم جرافيكي، وإنشاء مواقع.',
    ];

    public function __construct(private readonly string $locale)
    {
    }

    public function register(): void
    {
        add_action('wp_head', [$this, 'render'], 1);
    }

    public function render(): void
    {
        $isSingular = function_exists('is_singular') && is_singular();
        $excerpt = $isSingular && function_exists('get_the_excerpt') ? (string) get_the_excerpt() : '';
        $description = $this->describe($isSingular, $excerpt, $this->brandDescription());

        $title = function_exists('wp_get_document_title') ? (string) wp_get_document_title() : $this->brandName();
        $url = function_exists('home_url') ? (string) home_url(add_query_arg([])) : '';

        $tags = [
            '<meta name="description" content="' . esc_attr($description) . '" />',
            '<meta property="og:title" content="' . esc_attr($title) . '" />',
            '<meta property="og:description" content="' . esc_attr($description) . '" />',
            '<meta property="og:type" content="website" />',
            '<meta property="og:url" content="' . esc_url($url) . '" />',
            '<meta name="twitter:card" content="summary_large_image" />',
            '<meta name="twitter:title" content="' . esc_attr($title) . '" />',
            '<meta name="twitter:description" content="' . esc_attr($description) . '" />',
        ];

        echo "\n" . implode("\n", $tags) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- each value escaped above
    }

    public function describe(bool $isSingular, string $excerpt, string $brand): string
    {
        $excerpt = trim(wp_strip_all_tags($excerpt));
        if ($isSingular && $excerpt !== '') {
            return $this->truncate($excerpt);
        }

        return $brand;
    }

    public function brandDescription(): string
    {
        return self::BRAND[$this->locale] ?? self::BRAND['en'];
    }

    private function brandName(): string
    {
        return $this->locale === 'ar' ? 'بيريجو' : 'Perego';
    }

    private function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, self::MAX_LENGTH - 1)) . '…';
    }
}
