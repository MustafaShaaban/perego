<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Services;

defined('ABSPATH') || exit;

use PeregoSite\Language\FallbackLanguageDriver;
use PeregoSite\Language\LanguageDriver;
use PeregoSite\Language\PolylangLanguageDriver;

/**
 * Resolves the active {@see LanguageDriver}: Polylang when active, a same-effect fallback
 * otherwise (constitution IX — Polylang is never a hard dependency). Bound as a container
 * singleton in {@see \PeregoSite\PeregoSiteServiceProvider} so every consumer depends on this
 * service, never on a concrete driver.
 */
final class LanguageService
{
    private ?LanguageDriver $driver = null;

    /**
     * @param array<string,string> $cookie         typically `$_COOKIE`
     * @param string                $requestUri     typically `$_SERVER['REQUEST_URI']`
     * @param bool|null             $polylangActive explicit override for tests; null auto-detects
     *                                              via `function_exists('pll_current_language')`
     */
    public function __construct(
        private readonly array $cookie,
        private readonly string $requestUri = '/',
        private readonly ?bool $polylangActive = null,
    ) {
    }

    public function driver(): LanguageDriver
    {
        if ($this->driver === null) {
            $this->driver = $this->isPolylangActive()
                ? new PolylangLanguageDriver()
                : new FallbackLanguageDriver($this->cookie, $this->requestUri);
        }

        return $this->driver;
    }

    private function isPolylangActive(): bool
    {
        return $this->polylangActive ?? function_exists('pll_current_language');
    }
}
