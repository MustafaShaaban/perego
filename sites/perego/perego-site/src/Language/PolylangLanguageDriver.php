<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Language;

defined('ABSPATH') || exit;

use InvalidArgumentException;

/**
 * Adapts Polylang's own functions to {@see LanguageDriver}, so the rest of the site depends only
 * on the interface (constitution IX). `LanguageService` only instantiates this class when
 * Polylang's functions actually exist — this class assumes they do.
 */
final class PolylangLanguageDriver implements LanguageDriver
{
    private const RTL_LOCALES = ['ar'];

    public function currentLocale(): string
    {
        $locale = (string) pll_current_language();

        return $locale === '' ? 'en' : $locale;
    }

    public function isRtl(): bool
    {
        return in_array($this->currentLocale(), self::RTL_LOCALES, true);
    }

    public function availableLocales(): array
    {
        /** @var list<string> $locales */
        $locales = pll_languages_list();

        return $locales;
    }

    public function urlFor(string $locale): string
    {
        /** @var list<array{slug: string, url: string}> $translations */
        $translations = pll_the_languages(['raw' => true]);

        foreach ($translations as $translation) {
            if ($translation['slug'] === $locale) {
                return $translation['url'];
            }
        }

        throw new InvalidArgumentException(sprintf('No Polylang translation available for locale "%s".', $locale));
    }

    public function managesLanguageViaUrl(): bool
    {
        return true;
    }

    /**
     * Resolve a site path to its current-locale URL. Four shapes cover the whole primary nav:
     *  - the home path / in-page anchors (`/`, `/#about`) → the localized home (`pll_home_url`);
     *  - a real page or CPT single (`/contact`, `/services/<slug>`) → that entity's translation permalink;
     *  - the blog index (`/journal`, the `page_for_posts`) → the posts page's translation permalink;
     *  - a CPT archive (`/work`) → the same path under the language directory prefix.
     * Any failure degrades to the plain site URL — never a broken link.
     */
    public function localizedUrl(string $path): string
    {
        $path = $path === '' ? '/' : $path;
        $hash = strpos($path, '#');
        $fragment = $hash === false ? '' : substr($path, $hash);
        $base = $hash === false ? $path : substr($path, 0, $hash);
        $base = $base === '' ? '/' : $base;
        $locale = $this->currentLocale();

        if ($base === '/') {
            return (string) pll_home_url($locale) . $fragment;
        }

        $translated = $this->translatedEntityUrl($base);

        return ($translated ?? $this->languagePrefixedUrl($base, $locale)) . $fragment;
    }

    /**
     * The translation permalink for the page/CPT-single/blog-index a path points at, or null when the
     * path is not an entity (e.g. a CPT archive).
     */
    private function translatedEntityUrl(string $base): ?string
    {
        $postId = (int) url_to_postid((string) home_url($base));

        // The blog index (posts page) never reverse-resolves via url_to_postid. Match it by its
        // DEFAULT-language permalink — on a non-default request Polylang filters `page_for_posts` and
        // the permalink to the current language, so normalise to the default post first, then compare
        // against the (default-language) nav path.
        if ($postId === 0) {
            $blogId = (int) (pll_get_post((int) get_option('page_for_posts'), (string) pll_default_language()) ?: get_option('page_for_posts'));
            if ($blogId > 0 && untrailingslashit((string) get_permalink($blogId)) === untrailingslashit((string) home_url($base))) {
                $postId = $blogId;
            }
        }

        if ($postId === 0) {
            return null;
        }

        $url = $this->localizedPermalink($postId);

        return $url === '' ? null : $url;
    }

    /**
     * The chosen record's translation for the current locale, falling back to the record itself when
     * Polylang has no translation linked for that language (a half-translated site still links
     * somewhere real rather than nowhere).
     */
    public function localizedPermalink(int $postId): string
    {
        if ($postId <= 0) {
            return '';
        }

        $translatedId = (int) (pll_get_post($postId, $this->currentLocale()) ?: $postId);
        $url = get_permalink($translatedId);

        return is_string($url) ? $url : '';
    }

    /**
     * A path under the language directory prefix (non-default locale only) — for CPT archives, which
     * Polylang serves at `/<lang>/<path>`. The default language keeps the bare path.
     */
    private function languagePrefixedUrl(string $base, string $locale): string
    {
        if ($locale === (string) pll_default_language()) {
            return (string) home_url($base);
        }

        return (string) home_url('/' . $locale . '/' . trim($base, '/') . '/');
    }
}
