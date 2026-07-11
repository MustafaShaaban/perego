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
}
