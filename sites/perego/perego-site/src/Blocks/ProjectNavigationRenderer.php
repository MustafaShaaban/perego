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

    public function render(?WP_Post $project, string $surface = 'all'): string
    {
        if (! $project instanceof WP_Post) {
            return '';
        }

        $labels = $this->content->projectLabels();
        $adjacent = $this->projects->adjacentFor($project);
        $related = $this->projects->relatedFor($project);
        if ($surface === 'adjacent') {
            return $this->adjacent($adjacent['previous'], $adjacent['next'], $labels);
        }

        if ($surface === 'related') {
            return $this->related($related, $labels);
        }

        return $this->adjacent($adjacent['previous'], $adjacent['next'], $labels)
            . $this->related($related, $labels);
    }

    /** @param array<string, mixed> $labels */
    private function adjacent(?WP_Post $previous, ?WP_Post $next, array $labels): string
    {
        if (! $previous instanceof WP_Post && ! $next instanceof WP_Post) {
            return '';
        }

        $html = '<nav class="pagination" aria-label="' . esc_attr($this->content->heading()) . '" style="margin-top:clamp(32px,4vw,52px);">';
        $html .= $previous instanceof WP_Post ? '<a rel="prev" href="' . esc_url(get_permalink($previous)) . '">&#8249; ' . esc_html((string) $labels['prev']) . '</a>' : '<span></span>';
        $html .= $next instanceof WP_Post ? '<a rel="next" href="' . esc_url(get_permalink($next)) . '">' . esc_html((string) $labels['next']) . ' &#8250;</a>' : '<span></span>';

        return $html . '</nav>';
    }

    /** @param list<WP_Post> $related @param array<string, mixed> $labels */
    private function related(array $related, array $labels): string
    {
        if ($related === []) {
            return '';
        }

        $html = '<h2 class="section-title" style="text-align:center;margin-bottom:clamp(24px,3vw,38px);">'
            . esc_html((string) $labels['relatedTitle']) . '</h2><div class="blog-grid">';
        foreach ($related as $item) {
            $html .= $this->card($item);
        }
        $html .= '</div><div style="text-align:center;margin-top:clamp(28px,4vw,44px);"><a class="btn btn--accent" href="'
            . esc_url(home_url('/contact/')) . '">' . esc_html((string) $labels['ctaButton']) . '</a></div>';

        return $html;
    }

    private function card(WP_Post $project): string
    {
        $card = $this->projects->toGridCard($project, $this->content);
        $html = '<a class="post-card" href="' . esc_url($card['url']) . '">';
        if ($card['thumbUrl'] !== '') {
            $html .= '<div class="post-card__media"><img src="' . esc_url($card['thumbUrl']) . '" alt="' . esc_attr($card['thumbAlt']) . '" loading="lazy" /></div>';
        }
        $html .= '<div class="post-card__body"><span class="post-card__cat">' . esc_html($card['categoryLabel']) . '</span>';
        $html .= '<h3 class="post-card__title">' . esc_html($card['title']) . '</h3>';
        $html .= '<p class="post-card__excerpt">' . esc_html($card['excerpt']) . '</p></div></a>';

        return $html;
    }
}
