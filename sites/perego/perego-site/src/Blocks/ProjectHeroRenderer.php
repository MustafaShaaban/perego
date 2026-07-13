<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\PortfolioContent;
use WP_Post;

/**
 * Server-renders the perego-theme/project-hero block (spec 003 / M3, US2): the case-study hero —
 * a breadcrumb (Home / Work / project), the project's service category as an eyebrow, the project
 * title as the page H1, a demo-content notice, and the meta list (client / year / our role /
 * deliverables) read from post meta. Structural chrome + queried data; the narrative prose lives in
 * the editable post content. Ported from the handoff `project.html` `.post-hero` + meta `dl`.
 */
final class ProjectHeroRenderer
{
    public function __construct(private readonly PortfolioContent $content)
    {
    }

    public function render(?WP_Post $project): string
    {
        if (! $project instanceof WP_Post) {
            return '';
        }

        $labels = $this->content->projectLabels();
        $categoryName = $this->categoryName($project->ID);

        $html = '<div class="post-hero">';
        $html .= '<div class="post-hero__inner">';
        $html .= '<nav class="page-crumb" style="justify-content:center;" aria-label="Breadcrumb">';
        $html .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($this->content->uiHome()) . '</a>';
        $html .= '<span aria-hidden="true">/</span>';
        $html .= '<a href="' . esc_url(home_url('/work')) . '">' . esc_html($this->content->heading()) . '</a>';
        $html .= '<span aria-hidden="true">/</span>';
        $html .= '<span aria-current="page">' . esc_html(__('Project', 'perego-site')) . '</span>';
        $html .= '</nav>';

        if ($categoryName !== '') {
            $html .= '<span class="post-cat" style="margin-top:16px;">' . esc_html($categoryName) . '</span>';
        }

        $html .= '<h1 class="post-title">' . esc_html(get_the_title($project)) . '</h1>';
        $html .= '</div>';

        if (has_post_thumbnail($project->ID)) {
            $html .= '<div class="post-featured">'
                . get_the_post_thumbnail($project->ID, 'large', ['loading' => 'eager'])
                . '</div>';
        }

        $html .= '<dl class="project-hero__meta" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:20px 32px;margin:0 0 8px;padding:24px 0;border-block:1px solid rgba(255,255,255,0.1);">';
        $html .= $this->metaItem($labels['clientLabel'], (string) get_post_meta($project->ID, '_perego_client', true));
        $html .= $this->metaItem($labels['yearLabel'], (string) get_post_meta($project->ID, '_perego_year', true));
        $html .= $this->metaItem($labels['roleLabel'], (string) get_post_meta($project->ID, '_perego_role', true));
        $html .= $this->metaItem($labels['deliverablesLabel'], (string) get_post_meta($project->ID, '_perego_deliverables', true));
        $html .= '</dl>';

        $html .= '</div>';

        return $html;
    }

    private function metaItem(string $label, string $value): string
    {
        if ($value === '') {
            return '';
        }

        return '<div class="project-hero__meta-item">'
            . '<dt style="font-size:13px;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted-2);margin-bottom:4px;">' . esc_html($label) . '</dt>'
            . '<dd>' . esc_html($value) . '</dd>'
            . '</div>';
    }

    private function categoryName(int $postId): string
    {
        $terms = get_the_terms($postId, 'perego_project_category');
        if (! is_array($terms) || $terms === []) {
            return '';
        }

        return $this->content->categoryLabel($terms[0]->slug);
    }
}
