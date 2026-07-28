<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use WP_Post;

/**
 * Server-renders the single-post share row (client request 2026-07-27: the journal had no sharing UI).
 *
 * Plain share URLs, no third-party script. Each network's endpoint is a documented GET URL, so the row
 * costs nothing at load, cannot track the reader before they choose to share, and keeps working when a
 * network changes its widget. That also keeps it inside the constitution's "no CSS/JS frameworks" rule
 * without needing an exception.
 *
 * The links are real anchors — shareable, middle-clickable, keyboard-reachable — not buttons wired to
 * `window.open`. Copy-link is the one control that genuinely needs script, so it is a `<button>` that
 * degrades to being simply absent of behaviour without JS (see share/view.js).
 */
final class PostShareRenderer
{
    public function render(?WP_Post $post): string
    {
        if (! $post instanceof WP_Post) {
            return '';
        }

        $url = (string) get_permalink($post);
        $title = (string) get_the_title($post);

        if ($url === '') {
            return '';
        }

        $html = '<div class="post-share" data-perego-share>';
        $html .= '<span class="post-share__label">' . esc_html__('Share this article', 'perego-site') . '</span>';
        $html .= '<ul class="post-share__list">';

        foreach ($this->networks($url, $title) as $network) {
            /* translators: %s: social network name. */
            $aria = sprintf(__('Share on %s', 'perego-site'), $network['name']);

            $html .= '<li><a class="post-share__link" href="' . esc_url($network['href']) . '" '
                . 'target="_blank" rel="noopener noreferrer" '
                . 'aria-label="' . esc_attr($aria) . '">'
                . '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
                . '<path fill="currentColor" d="' . esc_attr($network['icon']) . '" /></svg>'
                . '</a></li>';
        }

        $html .= '<li><button type="button" class="post-share__link post-share__copy" '
            . 'data-perego-copy="' . esc_attr($url) . '" '
            . 'aria-label="' . esc_attr__('Copy link', 'perego-site') . '">'
            . '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="' . esc_attr(self::ICONS['copy']) . '" /></svg>'
            . '<span class="post-share__copied" aria-live="polite" hidden>' . esc_html__('Copied', 'perego-site') . '</span>'
            . '</button></li>';

        $html .= '</ul></div>';

        return $html;
    }

    /**
     * @return list<array{name: string, href: string, icon: string}>
     */
    private function networks(string $url, string $title): array
    {
        $encodedUrl = rawurlencode($url);
        $encodedTitle = rawurlencode($title);

        return [
            [
                'name' => 'X',
                'href' => 'https://twitter.com/intent/tweet?url=' . $encodedUrl . '&text=' . $encodedTitle,
                'icon' => self::ICONS['x'],
            ],
            [
                'name' => 'LinkedIn',
                'href' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encodedUrl,
                'icon' => self::ICONS['linkedin'],
            ],
            [
                'name' => 'Facebook',
                'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl,
                'icon' => self::ICONS['facebook'],
            ],
            [
                'name' => 'WhatsApp',
                // wa.me takes one pre-composed text parameter, so title and URL are joined before encoding.
                'href' => 'https://wa.me/?text=' . rawurlencode($title . ' ' . $url),
                'icon' => self::ICONS['whatsapp'],
            ],
        ];
    }


    /** @var array<string, string> */
    private const ICONS = [
        'x' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
        'linkedin' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0z',
        'facebook' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073',
        'whatsapp' => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413',
        'copy' => 'M16 1H4a2 2 0 0 0-2 2v14h2V3h12zm3 4H8a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2m0 16H8V7h11z',
    ];
}
