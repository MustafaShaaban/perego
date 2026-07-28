<?php

/**
 * Seeds real per-shape crops so the services mosaic demonstrates itself (owner, 2026-07-28).
 *
 * {@see ProjectThumbnails} lets an editor upload a crop per shape, and falls back to the nearest
 * shape and finally the featured image when they have not. That fallback is good enough that the
 * feature is invisible on seeded content: every tile shows the same centre-cropped still, so nothing
 * on screen shows what the four fields are for. This gives a handful of projects genuine crops.
 *
 * Three steps, because the cropping itself belongs to `sharp` rather than to PHP:
 *
 *   1. SEED_CROPS_MODE=manifest wp eval 'require ".../seed-project-crops.php";' --path=wp
 *   2. node sites/perego/perego-site/scripts/generate-project-crops.mjs --manifest <printed path>
 *   3. SEED_CROPS_MODE=import   wp eval 'require ".../seed-project-crops.php";' --path=wp
 *
 * The same fetch-then-import shape as `scripts/fetch-portfolio-logos.mjs` + `import-portfolio-logos.php`.
 *
 * Web-category projects are skipped throughout: they render through WebShowcaseRenderer, one fixed
 * card shape showing the client's logo, and never read a crop. Only English posts are touched — an
 * Arabic project inherits its English record's media.
 *
 * Idempotent: a project that already holds a crop for a shape keeps it, in both modes.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ProjectThumbnails;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

/** The mosaic lays out fifteen tiles; crops beyond that would never be seen on any one page. */
const PEREGO_CROP_PROJECT_LIMIT = 15;

$mode = getenv('SEED_CROPS_MODE') ?: 'manifest';
if (! in_array($mode, ['manifest', 'import'], true)) {
    WP_CLI::error("Unknown SEED_CROPS_MODE '{$mode}' — expected 'manifest' or 'import'.");
}

$dir = rtrim(getenv('SEED_CROPS_DIR') ?: (get_temp_dir() . 'perego-crops'), '/\\');
$manifestPath = $dir . '/manifest.json';

/** The non-web projects that still want at least one crop, with the source to cut it from. */
$targets = static function (): array {
    $rows = [];

    $posts = get_posts([
        'post_type' => ProjectPostType::POST_TYPE,
        'post_status' => 'publish',
        'numberposts' => 200,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'lang' => 'en',
        'tax_query' => [[
            'taxonomy' => ProjectPostType::TAXONOMY,
            'field' => 'slug',
            // Everything the mosaic actually renders. 'web' is absent on purpose.
            'terms' => ['video', 'motion', 'design'],
        ]],
    ]);

    foreach ($posts as $post) {
        if (count($rows) >= PEREGO_CROP_PROJECT_LIMIT) {
            break;
        }

        $sourceId = (int) get_post_thumbnail_id($post->ID);
        if ($sourceId === 0) {
            continue;
        }

        $source = get_attached_file($sourceId);
        if (! is_string($source) || ! file_exists($source)) {
            WP_CLI::warning("Featured image file missing for #{$post->ID} — skipped.");
            continue;
        }

        $missing = [];
        foreach (ProjectThumbnails::SHAPES as $shape => $spec) {
            if ((int) get_post_meta($post->ID, $spec['meta'], true) === 0) {
                $missing[$shape] = ['width' => $spec['width'], 'height' => $spec['height']];
            }
        }

        if ($missing === []) {
            continue;
        }

        $rows[] = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'source' => $source,
            'shapes' => $missing,
        ];
    }

    return $rows;
};

if ($mode === 'manifest') {
    if (! wp_mkdir_p($dir)) {
        WP_CLI::error("Could not create {$dir}.");
    }

    $rows = $targets();
    if ($rows === []) {
        WP_CLI::success('Every eligible project already has all four crops — nothing to generate.');

        return;
    }

    file_put_contents($manifestPath, (string) wp_json_encode([
        'dir' => $dir,
        'projects' => $rows,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $count = count($rows);
    WP_CLI::success("Manifest written for {$count} project(s): {$manifestPath}");
    WP_CLI::log('Next: node sites/perego/perego-site/scripts/generate-project-crops.mjs --manifest ' . $manifestPath);

    return;
}

if (! file_exists($manifestPath)) {
    WP_CLI::error("No manifest at {$manifestPath} — run SEED_CROPS_MODE=manifest first.");
}

$manifest = json_decode((string) file_get_contents($manifestPath), true);
if (! is_array($manifest) || ! isset($manifest['projects'])) {
    WP_CLI::error("Unreadable manifest at {$manifestPath}.");
}

$assigned = 0;
$missing = 0;

foreach ($manifest['projects'] as $row) {
    $postId = (int) ($row['id'] ?? 0);
    if ($postId === 0 || get_post_type($postId) !== ProjectPostType::POST_TYPE) {
        continue;
    }

    foreach (array_keys((array) ($row['shapes'] ?? [])) as $shape) {
        $spec = ProjectThumbnails::SHAPES[$shape] ?? null;
        if ($spec === null) {
            continue;
        }

        // Re-checked rather than trusted from the manifest: an editor may have uploaded the real
        // crop between the two steps, and a seed must never overwrite that.
        if ((int) get_post_meta($postId, $spec['meta'], true) > 0) {
            continue;
        }

        $file = $dir . '/' . $postId . '-' . $shape . '.webp';
        if (! file_exists($file)) {
            $missing++;
            continue;
        }

        $attachmentId = (int) WP_CLI::runcommand(
            sprintf(
                'media import %s --post_id=%d --porcelain --title=%s',
                escapeshellarg($file),
                $postId,
                escapeshellarg(sprintf('%s (%s crop)', (string) ($row['title'] ?? ''), $shape))
            ),
            ['return' => true, 'parse' => false]
        );

        if ($attachmentId === 0) {
            WP_CLI::warning("Import failed for #{$postId} {$shape}.");
            continue;
        }

        update_post_meta($postId, $spec['meta'], $attachmentId);
        $assigned++;
    }
}

if ($missing > 0) {
    WP_CLI::warning("{$missing} crop file(s) were named in the manifest but not found — did step 2 run?");
}

WP_CLI::success("Per-shape crops seeded — {$assigned} assigned (idempotent).");
