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
 * Server-renders the perego-theme/related-posts block: the journal single's "Related articles"
 * section (handoff single-post.html:150-181) — a hairline-topped band with a centered title and a
 * 3-card `.blog-grid` of navigational post cards (media / category / title / "date · N min read").
 * Cards NAVIGATE — journal cards never open the lightbox (handoff §8.2).
 *
 * `render()` takes an already-resolved post list so it stays a pure function of its inputs; the
 * block's render callback does the JournalRepository query.
 */
final class RelatedPostsRenderer
{
    public function __construct(
        private readonly GlobalContent $content,
        private readonly PostReadingTimeRenderer $readingTime,
    ) {
    }

    /**
     * @param list<WP_Post> $posts
     */
    public function render(array $posts): string
    {
        $cards = array_map(fn (WP_Post $post): string => $this->card($post), $posts);

        if ($cards === []) {
            return '';
        }

        $title = (string) ($this->content->journal()['relatedArticles'] ?? '');

        $html = '<section class="page-section post-related" style="border-top:1px solid rgba(255,255,255,0.08);" '
            . 'aria-labelledby="relatedArticles">';
        $html .= '<div class="container">';
        $html .= '<h2 class="section-title" id="relatedArticles" style="text-align:center;margin-bottom:clamp(24px,3vw,38px);">'
            . esc_html($title) . '</h2>';
        $html .= '<div class="blog-grid">' . implode('', $cards) . '</div>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    private function card(WP_Post $post): string
    {
        $html = '<a class="post-card reveal" href="' . esc_url((string) get_permalink($post)) . '">';

        $thumbId = get_post_thumbnail_id($post->ID);
        $thumbUrl = $thumbId ? (string) wp_get_attachment_image_url($thumbId, 'large') : '';
        if ($thumbUrl !== '') {
            $html .= '<div class="post-card__media"><img src="' . esc_url($thumbUrl) . '" alt="" loading="lazy" /></div>';
        }

        $html .= '<div class="post-card__body">';

        $terms = get_the_terms($post->ID, 'category');
        if (is_array($terms) && $terms !== []) {
            $html .= '<span class="post-card__cat">' . esc_html($terms[0]->name) . '</span>';
        }

        $html .= '<h3 class="post-card__title">' . esc_html(get_the_title($post)) . '</h3>';

        $meta = [(string) get_the_date('', $post)];
        $minutes = $this->readingTime->minutesFor($post);
        if ($minutes > 0) {
            $meta[] = $this->content->readTime($minutes);
        }
        $html .= '<span class="post-card__meta">' . esc_html(implode(' · ', array_filter($meta))) . '</span>';

        $html .= '</div>';
        $html .= '</a>';

        return $html;
    }
}
