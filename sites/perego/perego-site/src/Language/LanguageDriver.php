<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Language;

defined('ABSPATH') || exit;

/**
 * Abstracts the bilingual EN/AR mechanism behind an interface (constitution IX: no optional
 * dependency — Polylang here — is ever a hard one). `LanguageService` resolves whichever
 * implementation is available; every consumer (blocks, templates) depends on this interface only.
 */
interface LanguageDriver
{
    /**
     * The active locale, e.g. 'en' or 'ar'.
     */
    public function currentLocale(): string;

    /**
     * Whether the active locale renders right-to-left.
     */
    public function isRtl(): bool;

    /**
     * All locales the site offers, in a fixed display order.
     *
     * @return list<string>
     */
    public function availableLocales(): array;

    /**
     * The URL that switches the current page to the given locale.
     */
    public function urlFor(string $locale): string;

    /**
     * Resolve a stable internal site path ('/', '/#about', '/contact', '/work', '/journal',
     * '/services/<slug>') to its absolute URL **in the current locale**. This is what nav links use so
     * they stay within the active language (e.g. on `/ar/` the Work link points at the Arabic archive,
     * not the English one). Any `#fragment` is preserved. Implementations must never return a broken
     * link — degrade to the site path on failure.
     */
    public function localizedUrl(string $path): string;

    /**
     * The permalink for a chosen record **in the current locale** (spec 021 T036). Where `localizedUrl()`
     * takes a stable site path, this takes a post the editor picked in the block Inspector — which they
     * will usually have picked in one language — and resolves the translation that belongs to the
     * language being rendered, so an Arabic page links to the Arabic record.
     *
     * Returns an empty string when the record is missing, unpublished, or otherwise has no permalink, so
     * callers can fall back to a stored custom URL rather than emit a broken link.
     */
    public function localizedPermalink(int $postId): string;

    /**
     * Whether the driver's own URLs fully carry the language (so switching is real navigation and
     * the client must NOT persist/override the language). True for Polylang (directory URLs like
     * /ar/…); false for the cookie/query fallback, which relies on client-side persistence.
     */
    public function managesLanguageViaUrl(): bool;
}
