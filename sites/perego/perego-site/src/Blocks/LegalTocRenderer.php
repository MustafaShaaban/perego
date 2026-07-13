<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\GlobalContent;
use WP_Post;

/**
 * Server-renders the perego-theme/legal-toc block (M4): the legal page's aside — a draft/legal-review
 * notice, the "Last updated" line (from page meta), and a table of contents generated **dynamically
 * from the page's own level-2 headings** (their anchors), per the handoff. Language-aware via
 * GlobalContent. Sits beside the editable legal body (post content) in the `legal` page template.
 */
final class LegalTocRenderer
{
    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(?WP_Post $page): string
    {
        if (! $page instanceof WP_Post) {
            return '';
        }

        $legal = $this->content->legal();
        $items = $this->headings($page->post_content);
        $lastUpdated = (string) get_post_meta($page->ID, '_perego_last_updated', true);

        $html = '<aside class="legal-toc" aria-label="' . esc_attr($legal['tocTitle']) . '">';

        $html .= '<p class="legal-toc__note" role="note">' . esc_html($legal['reviewNote']) . '</p>';

        if ($lastUpdated !== '') {
            $html .= '<p class="legal-toc__updated">' . esc_html($legal['lastUpdated'])
                . ': ' . esc_html($lastUpdated) . '</p>';
        }

        if ($items !== []) {
            $html .= '<nav aria-labelledby="legal-toc-title">';
            $html .= '<h2 class="legal-toc__title" id="legal-toc-title">' . esc_html($legal['tocTitle']) . '</h2>';
            $html .= '<ol class="legal-toc__list">';
            foreach ($items as $item) {
                $html .= '<li><a href="#' . esc_attr($item['anchor']) . '">' . esc_html($item['text']) . '</a></li>';
            }
            $html .= '</ol>';
            $html .= '</nav>';
        }

        $html .= '</aside>';

        return $html;
    }

    /**
     * Collect anchored level-2 headings from the page's block content, in document order.
     *
     * @return list<array{anchor: string, text: string}>
     */
    private function headings(string $content): array
    {
        if (! function_exists('parse_blocks')) {
            return [];
        }

        $items = [];
        $this->walk(parse_blocks($content), $items);

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @param list<array{anchor: string, text: string}> $items
     */
    private function walk(array $blocks, array &$items): void
    {
        foreach ($blocks as $block) {
            $isH2 = ($block['blockName'] ?? '') === 'core/heading'
                && (int) ($block['attrs']['level'] ?? 2) === 2;
            $anchor = (string) ($block['attrs']['anchor'] ?? '');

            if ($isH2 && $anchor !== '') {
                $text = trim(wp_strip_all_tags((string) ($block['innerHTML'] ?? '')));
                if ($text !== '') {
                    $items[] = ['anchor' => $anchor, 'text' => $text];
                }
            }

            if (! empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
                $this->walk($block['innerBlocks'], $items);
            }
        }
    }
}
