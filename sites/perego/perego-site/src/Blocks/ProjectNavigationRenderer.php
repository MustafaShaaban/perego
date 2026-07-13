<?php

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Content\PortfolioContent;
use PeregoSite\Repositories\ProjectRepository;
use WP_Post;

/** Renders the locked handoff's adjacent and related project surfaces. */
final class ProjectNavigationRenderer
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly PortfolioContent $content,
    ) {
    }

    public function render(?WP_Post $project): string
    {
        if (! $project instanceof WP_Post) {
            return '';
        }

        $labels = $this->content->projectLabels();
        $adjacent = $this->projects->adjacentFor($project);
        $related = $this->projects->relatedFor($project);
        $html = '<section class="project-followup">';
        $html .= $this->adjacent($adjacent['previous'], $adjacent['next'], $labels);

        if ($related !== []) {
            $html .= '<div class="project-followup__related"><h2>' . esc_html((string) $labels['relatedTitle']) . '</h2>';
            $html .= '<div class="blog-grid">';
            foreach ($related as $item) {
                $html .= $this->card($item);
            }
            $html .= '</div></div>';
        }

        $html .= '<p class="project-followup__cta"><a class="wp-block-button__link wp-element-button" href="'
            . esc_url(home_url('/contact/')) . '">' . esc_html((string) $labels['ctaButton']) . '</a></p>';
        $html .= '</section>';

        return $html;
    }

    /** @param array<string, mixed> $labels */
    private function adjacent(?WP_Post $previous, ?WP_Post $next, array $labels): string
    {
        if (! $previous instanceof WP_Post && ! $next instanceof WP_Post) {
            return '';
        }

        $html = '<nav class="project-followup__adjacent" aria-label="' . esc_attr($this->content->heading()) . '">';
        $html .= $previous instanceof WP_Post ? '<a rel="prev" href="' . esc_url(get_permalink($previous)) . '">&#8249; ' . esc_html((string) $labels['prev']) . '</a>' : '<span></span>';
        $html .= $next instanceof WP_Post ? '<a rel="next" href="' . esc_url(get_permalink($next)) . '">' . esc_html((string) $labels['next']) . ' &#8250;</a>' : '<span></span>';

        return $html . '</nav>';
    }

    private function card(WP_Post $project): string
    {
        $card = $this->projects->toGridCard($project, $this->content);
        $html = '<a class="post-card" href="' . esc_url($card['url']) . '">';
        if ($card['thumbUrl'] !== '') {
            $html .= '<span class="post-card__media"><img src="' . esc_url($card['thumbUrl']) . '" alt="' . esc_attr($card['thumbAlt']) . '" loading="lazy" /></span>';
        }
        $html .= '<span class="post-card__body"><span class="post-card__cat">' . esc_html($card['categoryLabel']) . '</span>';
        $html .= '<span class="post-card__title">' . esc_html($card['title']) . '</span>';
        $html .= '<span class="post-card__excerpt">' . esc_html($card['excerpt']) . '</span></span></a>';

        return $html;
    }
}
