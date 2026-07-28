<?php

/**
 * Carry existing projects onto the explicit tile-icon model (owner decision 2026-07-28).
 *
 * WHAT CHANGED. What a project's grid tile advertised used to be a consequence of its media: a video
 * always forced a ▶, two or more gallery images always forced a "Gallery" badge, and nothing could
 * turn either off. A project now carries `_perego_project_icon` — `none`, `play` or `gallery` — and
 * the tile shows what the editor chose. The tile's TRIGGER is unchanged and still derives from the
 * media, because what a tile opens and what it advertises are different questions.
 *
 * This writes the icon each project already displayed, so the services mosaic looks the same the
 * moment it runs. The work/portfolio grid gains icons it never had — it previously showed no
 * affordance at all, even on cards that open a lightbox.
 *
 * SAFETY. Idempotent (a project that already carries a stored icon is skipped), snapshots the values
 * it derives from into `_perego_project_migration_backup` first, batched, and `--dry-run` reports
 * without writing. Sweeps EN and AR, since Polylang gives a translation its own meta row.
 *
 * Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-project-icon.php";' --path=wp
 *   wp eval '$argv[]="--dry-run"; require "…/migrate-project-icon.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ProjectPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

const PEREGO_PROJECT_BACKUP_KEY = '_perego_project_migration_backup';

/** Batched rather than `numberposts => -1`: this loads full post objects. */
const PEREGO_PROJECT_BATCH = 100;

$dryRun = in_array('--dry-run', (array) ($argv ?? []), true);

$migrated = 0;
$skipped = 0;
$untouched = 0;
$offset = 0;

do {
    $projects = get_posts([
        'post_type' => ProjectPostType::POST_TYPE,
        'post_status' => 'any',
        'numberposts' => PEREGO_PROJECT_BATCH,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'ASC',
        'lang' => '', // EN and AR — each translation carries its own meta.
    ]);
    $offset += PEREGO_PROJECT_BATCH;

    foreach ($projects as $project) {
        $postId = (int) $project->ID;

        /*
         * `metadata_exists`, not `get_post_meta`: a registered meta default is returned for a key
         * that was never written, so `get_post_meta` cannot tell "unset" from "explicitly none".
         * The icon is registered without a default precisely so this stays true, but reading it this
         * way means the marker keeps working even if someone adds one later.
         */
        if (metadata_exists('post', $postId, ProjectPostType::META_ICON)) {
            $skipped++;
            continue;
        }

        $videoUrl = trim((string) get_post_meta($postId, ProjectPostType::META_VIDEO_URL, true));
        $gallery = ProjectPostType::sanitizeIntList(get_post_meta($postId, ProjectPostType::META_GALLERY, true));
        $icon = peregoDeriveProjectIcon($videoUrl, $gallery);

        /*
         * A derived `none` is LEFT UNSET rather than written. For an English project the two are
         * identical — an empty value sanitizes to `none`. For a translation they are not: Polylang
         * does not copy meta, so an Arabic project reads its English record's icon through
         * `ProjectRepository::iconFor()`. Writing `none` onto it would be a stored value, which stops
         * that fallback and silently strips the icon from every Arabic grid.
         */
        if ($icon === 'none') {
            $untouched++;
            continue;
        }

        if ($dryRun) {
            WP_CLI::log(sprintf(
                '[dry-run] #%d %s → icon=%s',
                $postId,
                get_the_title($postId),
                $icon
            ));
            $migrated++;
            continue;
        }

        update_post_meta($postId, PEREGO_PROJECT_BACKUP_KEY, [
            ProjectPostType::META_VIDEO_URL => $videoUrl,
            ProjectPostType::META_GALLERY => $gallery,
        ]);
        update_post_meta($postId, ProjectPostType::META_ICON, $icon);

        $migrated++;
    }
} while (count($projects) === PEREGO_PROJECT_BATCH);

WP_CLI::success(sprintf(
    '%s%d project(s) given an icon, %d already done, %d left with none.',
    $dryRun ? '[dry-run] ' : '',
    $migrated,
    $skipped,
    $untouched
));

/**
 * The icon a project's tile already showed.
 *
 * Mirrors the precedence the renderer used before this field existed: a video won outright, then a
 * multi-image gallery, and a single image showed nothing. A one-image "gallery" was never a gallery
 * badge, so it stays iconless.
 *
 * @param list<int> $gallery
 */
function peregoDeriveProjectIcon(string $videoUrl, array $gallery): string
{
    if ($videoUrl !== '') {
        return 'play';
    }

    return count($gallery) > 1 ? 'gallery' : 'none';
}
