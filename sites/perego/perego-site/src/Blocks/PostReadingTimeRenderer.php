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
 * Server-renders the perego-theme/post-reading-time block: the "N min read" segment of the single-post
 * meta row (handoff single-post.html:71). Estimates from the post body at 200 words/minute, floored to a
 * one-minute minimum. Language-aware via GlobalContent (handoff `readTime` string). Exists because the
 * single-post meta row is otherwise composed of native WP blocks with no locale-aware reading estimate.
 */
final class PostReadingTimeRenderer
{
    private const WORDS_PER_MINUTE = 200;

    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(?WP_Post $post): string
    {
        if (! $post instanceof WP_Post) {
            return '';
        }

        $minutes = $this->minutesFor($post);
        if ($minutes === 0) {
            return '';
        }

        return '<span class="post-single__readtime">' . esc_html($this->content->readTime($minutes)) . '</span>';
    }

    /**
     * The estimated reading minutes for a post — 0 when it has no body. Public so other journal
     * surfaces (the related-articles cards) share the exact same estimate.
     */
    public function minutesFor(WP_Post $post): int
    {
        $words = $this->wordCount($post->post_content);
        if ($words === 0) {
            return 0;
        }

        return (int) max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /**
     * Whitespace-delimited word count over the plain-text body. Uses a unicode-aware split rather than
     * str_word_count() so Arabic content (which str_word_count treats as zero words) counts correctly.
     */
    private function wordCount(string $content): int
    {
        $text = trim(wp_strip_all_tags(strip_shortcodes($content)));
        if ($text === '') {
            return 0;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($words) ? count($words) : 0;
    }
}
