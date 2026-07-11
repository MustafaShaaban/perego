<?php

/**
 * Seed example projects for the portfolio grid + case-study singles (spec 003 / M3). Idempotent:
 * creates a project only if one with its slug does not already exist, and never overwrites later
 * editor changes. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-projects.php";' --path=wp
 *
 * These are the handoff's **PLACEHOLDER** projects (client shown as "Sample Client", body copy is
 * explicit "replace me" guidance) — replace with Perego's real work before launch. **Do not invent
 * results or metrics.** The five case-study sections (Overview / Challenge / Approach / Solution /
 * Result) are written as editable block markup into post_content (editor-canvas rule); the section
 * titles come from PortfolioContent so they are translation-ready. English is seeded here; AR project
 * translations are deferred with the real project data (owner-supplied). See CONTENT_MODEL.md.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\PortfolioContent;
use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

$pllReady = function_exists('pll_set_post_language') && function_exists('pll_get_post_language')
    && in_array('en', function_exists('pll_languages_list') ? pll_languages_list() : [], true);

$categories = ProjectPostType::CATEGORIES; // slug => label

foreach ($categories as $slug => $label) {
    if (! term_exists($slug, ProjectPostType::TAXONOMY)) {
        wp_insert_term($label, ProjectPostType::TAXONOMY, ['slug' => $slug]);
        WP_CLI::log("Created category: {$slug}");
    }
}

$content = new PortfolioContent('en');
$labels = $content->projectLabels();

/**
 * Build the editable five-section case-study body. Copy is the handoff's explicit placeholder
 * guidance (clearly marked demo) — never asserted business facts. `$labels` supplies section titles.
 */
$buildNarrative = static function (array $labels): string {
    $sections = [
        [$labels['overviewTitle'], 'A short summary of the project, the brand, and what Perego set out to achieve. Replace this with the real project brief and context.'],
        [$labels['challengeTitle'], 'Describe the problem the client came with — the constraint, the deadline, or the creative bar that made the project interesting.'],
        [$labels['approachTitle'], 'Explain how the team approached the work: the concept, the process, and the key creative or technical decisions.'],
        [$labels['solutionTitle'], 'Summarise what was delivered and why it works — tie it back to the challenge.'],
        [$labels['resultTitle'], 'State the outcome. Do not invent metrics — use only real, client-approved results; otherwise describe the deliverables qualitatively.'],
    ];

    $blocks = '<!-- wp:paragraph {"className":"project-demo-note"} --><p class="project-demo-note"><em>Example case study — replace all project details, media, and results with Perego\'s real project data.</em></p><!-- /wp:paragraph -->' . "\n\n";

    foreach ($sections as [$title, $body]) {
        $blocks .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html($title) . '</h2><!-- /wp:heading -->' . "\n";
        $blocks .= '<!-- wp:paragraph --><p>' . esc_html($body) . '</p><!-- /wp:paragraph -->' . "\n\n";
    }

    return $blocks;
};

/** @var list<array{title: string, cat: string, year: string, role: string, deliverables: string}> $projects */
$projects = [
    ['title' => 'Brand Film — Launch Campaign', 'cat' => 'video', 'year' => '2026', 'role' => 'Editing & Post-Production', 'deliverables' => 'Hero film, 3 social cutdowns'],
    ['title' => 'Product Teaser Cut', 'cat' => 'video', 'year' => '2025', 'role' => 'Editing & Post-Production', 'deliverables' => 'Teaser, vertical cut'],
    ['title' => 'Event Recap Edit', 'cat' => 'video', 'year' => '2026', 'role' => 'Editing', 'deliverables' => 'Recap film'],
    ['title' => 'Animated Explainer Series', 'cat' => 'motion', 'year' => '2026', 'role' => 'Concept, Storyboard, Animation', 'deliverables' => 'Explainer series'],
    ['title' => 'Logo Sting & Lower Thirds', 'cat' => 'motion', 'year' => '2025', 'role' => 'Motion Design', 'deliverables' => 'Logo sting, lower-thirds pack'],
    ['title' => 'Visual Identity System', 'cat' => 'design', 'year' => '2025', 'role' => 'Brand Identity', 'deliverables' => 'Logo, visual system'],
    ['title' => 'Campaign Key Visual Suite', 'cat' => 'design', 'year' => '2026', 'role' => 'Art Direction, Design', 'deliverables' => 'Key visuals, social templates'],
    ['title' => 'Multi-page Marketing Site', 'cat' => 'web', 'year' => '2026', 'role' => 'Design & Build', 'deliverables' => 'Multi-page website'],
    ['title' => 'Landing Page & Microsite', 'cat' => 'web', 'year' => '2025', 'role' => 'Design & Build', 'deliverables' => 'Landing page, microsite'],
];

$created = 0;

foreach ($projects as $project) {
    $slug = sanitize_title($project['title']);

    $existing = get_posts([
        'post_type' => ProjectPostType::POST_TYPE,
        'name' => $slug,
        'post_status' => 'any',
        'numberposts' => 1,
        'fields' => 'ids',
    ]);

    if ($existing !== []) {
        continue; // idempotent — never overwrite later editor changes
    }

    $postId = wp_insert_post([
        'post_type' => ProjectPostType::POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $project['title'],
        'post_name' => $slug,
        'post_content' => $buildNarrative($labels),
        'post_excerpt' => 'Client: Sample Client · ' . $project['year'],
    ], true);

    if (is_wp_error($postId)) {
        WP_CLI::warning("Failed: {$project['title']} — " . $postId->get_error_message());
        continue;
    }

    wp_set_object_terms($postId, $project['cat'], ProjectPostType::TAXONOMY);
    update_post_meta($postId, '_perego_client', 'Sample Client');
    update_post_meta($postId, '_perego_year', $project['year']);
    update_post_meta($postId, '_perego_role', $project['role']);
    update_post_meta($postId, '_perego_deliverables', $project['deliverables']);

    if ($pllReady && ! pll_get_post_language($postId)) {
        pll_set_post_language($postId, 'en');
    }

    $created++;
}

WP_CLI::success("Seeded {$created} example project(s); " . count($projects) . ' total defined.');
