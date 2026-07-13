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

        $html = '<section class="project-hero">';
        $html .= '<nav class="project-hero__crumb" aria-label="Breadcrumb">';
        $html .= '<a href="' . esc_url(home_url('/')) . '">' . esc_html($this->content->uiHome()) . '</a>';
        $html .= '<span aria-hidden="true">/</span>';
        $html .= '<a href="' . esc_url(home_url('/work')) . '">' . esc_html($this->content->heading()) . '</a>';
        $html .= '</nav>';

        if ($categoryName !== '') {
            $html .= '<p class="project-hero__cat">' . esc_html($categoryName) . '</p>';
        }

        $html .= '<h1 class="project-hero__title">' . esc_html(get_the_title($project)) . '</h1>';

        if (has_post_thumbnail($project->ID)) {
            $html .= '<div class="project-hero__featured">'
                . get_the_post_thumbnail($project->ID, 'large', ['loading' => 'eager'])
                . '</div>';
        }

        $html .= '<dl class="project-hero__meta">';
        $html .= $this->metaItem($labels['clientLabel'], (string) get_post_meta($project->ID, '_perego_client', true));
        $html .= $this->metaItem($labels['yearLabel'], (string) get_post_meta($project->ID, '_perego_year', true));
        $html .= $this->metaItem($labels['roleLabel'], (string) get_post_meta($project->ID, '_perego_role', true));
        $html .= $this->metaItem($labels['deliverablesLabel'], (string) get_post_meta($project->ID, '_perego_deliverables', true));
        $html .= '</dl>';

        $html .= '</section>';

        return $html;
    }

    private function metaItem(string $label, string $value): string
    {
        if ($value === '') {
            return '';
        }

        return '<div class="project-hero__meta-item">'
            . '<dt>' . esc_html($label) . '</dt>'
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
