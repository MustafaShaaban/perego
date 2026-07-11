<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Language;

defined('ABSPATH') || exit;

use InvalidArgumentException;

/**
 * A same-effect language mechanism used when Polylang (or an equivalent) isn't active — the site
 * still fully supports EN/AR per constitution IX ("no optional dependency is a hard one"). The
 * front-end Interactivity API view-script is what actually performs the `lang`/`dir` swap and
 * persists the choice (a cookie, `perego_lang`); this driver only reads that same cookie
 * server-side so a first render already reflects the visitor's stored choice.
 */
final class FallbackLanguageDriver implements LanguageDriver
{
    private const COOKIE_NAME = 'perego_lang';

    private const RTL_LOCALES = ['ar'];

    /**
     * @param array<string,string> $cookie      typically `$_COOKIE`
     * @param string                $requestUri typically `$_SERVER['REQUEST_URI']`
     */
    public function __construct(
        private readonly array $cookie,
        private readonly string $requestUri = '/',
    ) {
    }

    public function currentLocale(): string
    {
        $stored = $this->cookie[self::COOKIE_NAME] ?? null;

        return in_array($stored, $this->availableLocales(), true) ? $stored : 'en';
    }

    public function isRtl(): bool
    {
        return in_array($this->currentLocale(), self::RTL_LOCALES, true);
    }

    public function availableLocales(): array
    {
        return ['en', 'ar'];
    }

    public function urlFor(string $locale): string
    {
        if (! in_array($locale, $this->availableLocales(), true)) {
            throw new InvalidArgumentException(sprintf('Locale "%s" is not offered.', $locale));
        }

        [$path, $query] = array_pad(explode('?', $this->requestUri, 2), 2, '');

        parse_str($query, $params);
        $params['lang'] = $locale;

        return $path . '?' . http_build_query($params);
    }
}
