<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Resolves a block's `<name>En` / `<name>Ar` attribute pairs down to the current locale (spec 021).
 *
 * Perego blocks that live in a shared FSE template cannot have one attribute per language the way a
 * translated post can — the template is the same object in both languages — so bilingual copy is stored
 * as an En/Ar pair on the block and the renderer picks the current locale's half. That `$suffix =
 * $locale === 'ar' ? 'Ar' : 'En'` line was being written out again in every renderer (see
 * `SiteHeaderRenderer::renderCta()`); this holds it once.
 *
 * Only non-empty values are returned, so a caller can merge the result over its seed copy and have an
 * unedited block render exactly what it always did.
 */
final class LocalizedAttributes
{
    /**
     * @param array<string, mixed> $attributes the block's attributes
     * @param string               $locale     the current locale ('ar' selects the Arabic half)
     * @param list<string>         $keys       base attribute names, without the En/Ar suffix
     * @return array<string, string> the non-empty values, keyed by base name
     */
    public static function pick(array $attributes, string $locale, array $keys): array
    {
        $suffix = $locale === 'ar' ? 'Ar' : 'En';
        $picked = [];

        foreach ($keys as $key) {
            $value = $attributes[$key . $suffix] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $picked[$key] = $value;
            }
        }

        return $picked;
    }
}
