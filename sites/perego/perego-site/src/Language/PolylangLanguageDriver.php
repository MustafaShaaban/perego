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
}
