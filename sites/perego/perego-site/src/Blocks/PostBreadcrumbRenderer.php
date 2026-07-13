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
 * Server-renders the perego-theme/post-breadcrumb block: "Home / Journal / <post title>" above the
 * single-post title. Ported from the handoff `single-post.html`. Language-aware via GlobalContent —
 * the single-post template has no PHP renderer of its own (native WP post blocks only), so this small
 * block exists purely to make the breadcrumb locale-aware, mirroring JournalHeaderRenderer.
 */
final class PostBreadcrumbRenderer
{
    public function __construct(private readonly GlobalContent $content)
    {
    }

    public function render(?WP_Post $post): string
    {
        $title = $post instanceof WP_Post ? get_the_title($post) : '';

        $html = '<nav class="page-crumb" style="justify-content:center;" aria-label="' . esc_attr__('Breadcrumb', 'perego-site') . '">';
        $html .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($this->content->uiHome()) . '</a>';
        $html .= '<span aria-hidden="true">/</span>';
        $html .= '<a href="' . esc_url(home_url('/journal')) . '">' . esc_html($this->content->journal()['h1']) . '</a>';
        $html .= '<span aria-hidden="true">/</span>';
        $html .= '<span aria-current="page">' . esc_html($title) . '</span>';
        $html .= '</nav>';

        return $html;
    }
}
