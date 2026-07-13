<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Seo;

defined('ABSPATH') || exit;

use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;

/**
 * Emits JSON-LD structured data (schema.org) in the document head (M10 / SEO). Site-wide
 * Organization + WebSite on every route, plus a context node: Service for service singles, Article
 * for journal posts, CreativeWork for projects, and a BreadcrumbList where a trail exists.
 *
 * Only real, verifiable data is emitted — no fabricated ratings, reviews, awards, prices, or client
 * claims (handoff SEO_HANDOFF rule). Social profiles are omitted until real handles are supplied
 * (the prototype's are placeholders). Output is encoded with wp_json_encode.
 */
final class StructuredData
{
    public function register(): void
    {
        add_action('wp_head', [$this, 'render'], 20);
    }

    public function render(): void
    {
        $nodes = array_merge(
            [$this->organization(), $this->website()],
            $this->contextNodes()
        );

        foreach (array_filter($nodes) as $node) {
            echo '<script type="application/ld+json">'
                . wp_json_encode($node, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . '</script>' . "\n";
        }
    }

    /** @return array<string, mixed> */
    private function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => home_url('/#organization'),
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'description' => get_bloginfo('description'),
        ];
    }

    /** @return array<string, mixed> */
    private function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'publisher' => ['@id' => home_url('/#organization')],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}'),
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function contextNodes(): array
    {
        if (is_singular(ServicePostType::POST_TYPE)) {
            return [$this->service(), $this->breadcrumb([['Services', home_url('/services')], [get_the_title(), '']])];
        }

        if (is_singular(ProjectPostType::POST_TYPE)) {
            return [$this->creativeWork(), $this->breadcrumb([['Work', home_url('/work')], [get_the_title(), '']])];
        }

        if (is_singular('post')) {
            return [$this->article()];
        }

        return [];
    }

    /** @return array<string, mixed> */
    private function service(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Service',
            'name' => get_the_title(),
            'url' => get_permalink(),
            'provider' => ['@id' => home_url('/#organization')],
            'areaServed' => 'Arab region',
        ];
    }

    /** @return array<string, mixed> */
    private function creativeWork(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => get_the_title(),
            'url' => get_permalink(),
            'creator' => ['@id' => home_url('/#organization')],
        ];
    }

    /** @return array<string, mixed> */
    private function article(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'url' => get_permalink(),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => ['@type' => 'Person', 'name' => get_the_author()],
            'publisher' => ['@id' => home_url('/#organization')],
        ];
    }

    /**
     * @param list<array{0: string, 1: string}> $trail  [label, url] pairs; the last url may be empty.
     * @return array<string, mixed>
     */
    private function breadcrumb(array $trail): array
    {
        $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')]];
        $position = 2;

        foreach ($trail as [$label, $url]) {
            $item = ['@type' => 'ListItem', 'position' => $position, 'name' => $label];
            if ($url !== '') {
                $item['item'] = $url;
            }
            $items[] = $item;
            $position++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
}
