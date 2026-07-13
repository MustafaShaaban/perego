<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Seo;

defined('ABSPATH') || exit;

/**
 * Agent-readiness surface (spec Phase 10). Serves a real `/llms.txt` — the machine-readable index AI
 * agents and crawlers use to discover the site's primary content — built from the site's own name,
 * tagline, and real routes (no fabricated claims). This satisfies the `llms_txt` signal CoreX's own
 * ReadinessScorer (corex-config Insights) checks for, rather than duplicating that dashboard.
 *
 * It also emits the `x-default` hreflang alternate that Polylang Free's per-language alternates omit,
 * completing the EN/AR/x-default set the handoff SEO model requires. Pure builders (`llmsBody`,
 * `alternateXDefaultTag`, `isLlmsRequest`) are unit-tested; the WordPress glue stays thin.
 */
final class PeregoAgentReadiness
{
    /** The site's primary routes, in navigation order (mirrors the header nav — real URLs only). */
    private const ROUTES = [
        'Home' => '/',
        'Services' => '/services/',
        'Work' => '/work/',
        'Journal' => '/journal/',
        'Contact' => '/contact/',
    ];

    public function register(): void
    {
        add_action('init', [$this, 'maybeServeLlms']);
        add_action('wp_head', [$this, 'renderXDefault'], 21);
    }

    public function maybeServeLlms(): void
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (! $this->isLlmsRequest($requestUri)) {
            return;
        }

        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        // Plain-text output: the body is assembled from sanitized site data + literal routes, and the
        // nosniff + text/plain headers prevent HTML interpretation, so this is not an escaping context.
        echo $this->llmsBody($this->siteData());
        exit;
    }

    public function isLlmsRequest(string $requestUri): bool
    {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);

        return trim($path, '/') === 'llms.txt';
    }

    /**
     * @param array{name:string,description:string,home:string,sections:list<array{label:string,url:string}>} $data
     */
    public function llmsBody(array $data): string
    {
        $lines = [
            '# ' . $data['name'],
            '',
            '> ' . $data['description'],
            '',
            '## Primary sections',
            '',
        ];

        foreach ($data['sections'] as $section) {
            $lines[] = '- [' . $section['label'] . '](' . $section['url'] . ')';
        }

        $lines[] = '';
        $lines[] = '## Languages';
        $lines[] = '';
        $lines[] = '- English: ' . $data['home'];
        $lines[] = '- Arabic: ' . rtrim($data['home'], '/') . '/ar/';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function renderXDefault(): void
    {
        echo $this->alternateXDefaultTag($this->defaultHome()) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in builder
    }

    public function alternateXDefaultTag(string $home): string
    {
        return '<link rel="alternate" hreflang="x-default" href="' . esc_url($home) . '" />';
    }

    /**
     * @return array{name:string,description:string,home:string,sections:list<array{label:string,url:string}>}
     */
    private function siteData(): array
    {
        $sections = [];
        foreach (self::ROUTES as $label => $path) {
            $sections[] = ['label' => $label, 'url' => (string) home_url($path)];
        }

        $description = trim((string) get_bloginfo('description'));

        return [
            'name' => (string) get_bloginfo('name'),
            // Fall back to the handoff brand tagline when no site description is set (no empty blockquote).
            'description' => $description !== '' ? $description : 'Creative Studio',
            'home' => (string) home_url('/'),
            'sections' => $sections,
        ];
    }

    private function defaultHome(): string
    {
        return (string) home_url('/');
    }
}
