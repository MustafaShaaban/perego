<?php

/**
 * Carry existing clients onto the explicit behaviour model (owner decision 2026-07-28).
 *
 * WHAT CHANGED. What a client card does used to be inferred from which fields happened to be filled
 * in: a corporate tile opened a lightbox if it had a gallery and otherwise opened its own logo image,
 * and an individual card's action came from `_perego_client_video_type` (`embed`/`upload` → lightbox,
 * `external` → a link). Both inferences are gone. A card now carries `_perego_client_behavior` —
 * `none`, `lightbox` or `link` — and only acts when it says so. The individual card's single video
 * became an ordinary gallery entry, so both client types share one gallery, and the card's middle
 * line (`_perego_client_sub`) became the post's own editor content.
 *
 * This reads each client's old fields and writes the behaviour they already had, so nothing visibly
 * changes on the site except the corporate tiles whose only "gallery" was the implicit logo fallback
 * — those become the static tiles the owner asked for.
 *
 * SAFETY. Idempotent (a client that already carries a behaviour is skipped), snapshots every value it
 * is about to retire into `_perego_client_migration_backup` before writing, and only deletes the old
 * keys once the new ones are in place. `--dry-run` reports without writing anything. Covers EN and AR
 * posts alike, since Polylang gives a translation its own meta row.
 *
 * Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-client-behavior.php";' --path=wp
 *   wp eval '$argv[]="--dry-run"; require "…/migrate-client-behavior.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\PostTypes\ClientBehavior;
use PeregoSite\PostTypes\ClientPostType;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

/** The keys this migration retires, snapshotted together so a rollback has everything it needs. */
const PEREGO_CLIENT_LEGACY_KEYS = [
    '_perego_client_sub',
    '_perego_client_video_url',
    '_perego_client_video_type',
];

const PEREGO_CLIENT_BACKUP_KEY = '_perego_client_migration_backup';

$dryRun = in_array('--dry-run', (array) ($argv ?? []), true);

/** Batched rather than `numberposts => -1`: this loads full post objects, content included. */
const PEREGO_CLIENT_BATCH = 100;

$migrated = 0;
$skipped = 0;
$untouched = 0;
$contentMoved = 0;
$offset = 0;

do {
    $clients = get_posts([
        'post_type' => ClientPostType::POST_TYPE,
        'post_status' => 'any',
        'numberposts' => PEREGO_CLIENT_BATCH,
        'offset' => $offset,
        'orderby' => 'ID',
        'order' => 'ASC',
        'lang' => '', // EN and AR — each translation carries its own meta.
    ]);
    $offset += PEREGO_CLIENT_BATCH;

    foreach ($clients as $client) {
        $postId = (int) $client->ID;

        // Already migrated: a STORED behaviour is the marker, so a re-run is a no-op rather than a
        // second pass that would append the subtitle to the content twice.
        //
        // `metadata_exists`, not `get_post_meta`: a registered meta default is returned for a key
        // that was never written, so `get_post_meta` cannot tell "unset" from "explicitly set". The
        // first dry run of this script reported all 54 clients as already done for exactly that
        // reason. The registration no longer declares a default, and this no longer depends on it.
        if (metadata_exists('post', $postId, ClientPostType::META_BEHAVIOR)) {
            $skipped++;
            continue;
        }

        $subtitle = trim((string) get_post_meta($postId, '_perego_client_sub', true));
        $videoUrl = trim((string) get_post_meta($postId, '_perego_client_video_url', true));
        $videoType = (string) get_post_meta($postId, '_perego_client_video_type', true);
        $gallery = ClientPostType::sanitizeGallery(get_post_meta($postId, ClientPostType::META_GALLERY, true));

        ['behavior' => $behavior, 'gallery' => $newGallery, 'linkUrl' => $linkUrl] =
            ClientBehavior::derive($videoUrl, $videoType, $gallery);

        /*
         * A derived `none` is LEFT UNSET rather than written.
         *
         * For an English post the two are identical — an empty value sanitizes to `none`. For a
         * translation they are not: Polylang does not copy meta, so 27 of the Arabic clients here
         * carry no media of their own and read their English record's through `TranslatedMeta`.
         * Writing `none` onto them would be a stored value, which stops that fallback dead and
         * renders every Arabic card inert — the exact defect `TranslatedMeta` exists to prevent.
         * An unwritten key keeps inheriting; an editor can still choose `No actions` explicitly.
         */
        $writesBehavior = $behavior !== 'none';
        $hasLegacyData = $subtitle !== '' || $videoUrl !== '' || $videoType !== '';

        if (! $writesBehavior && ! $hasLegacyData) {
            $untouched++;
            continue;
        }

        if ($dryRun) {
            WP_CLI::log(sprintf(
                '[dry-run] #%d %s → %s, gallery=%d item(s)%s%s',
                $postId,
                get_the_title($postId),
                $writesBehavior ? 'behavior=' . $behavior : 'behavior left unset (inherits)',
                count($newGallery),
                $linkUrl !== '' ? ', link=' . $linkUrl : '',
                $subtitle !== '' ? ', subtitle → content' : ''
            ));
            $migrated++;
            // Counted here too, or the dry-run summary would report 0 subtitle moves while the lines
            // above plainly show them — a summary that disagrees with its own detail is worse than none.
            $contentMoved += $subtitle !== '' ? 1 : 0;
            continue;
        }

        // Snapshot BEFORE the first write, so an interrupted run still leaves a complete record.
        $backup = ['post_content' => $client->post_content];
        foreach (PEREGO_CLIENT_LEGACY_KEYS as $key) {
            $backup[$key] = get_post_meta($postId, $key, true);
        }
        $backup[ClientPostType::META_GALLERY] = $gallery;
        update_post_meta($postId, PEREGO_CLIENT_BACKUP_KEY, $backup);

        if ($writesBehavior) {
            update_post_meta($postId, ClientPostType::META_BEHAVIOR, $behavior);
        }

        if ($newGallery !== []) {
            update_post_meta($postId, ClientPostType::META_GALLERY, $newGallery);
        }

        if ($linkUrl !== '') {
            update_post_meta($postId, ClientPostType::META_LINK_URL, $linkUrl);
            update_post_meta($postId, ClientPostType::META_LINK_KIND, 'custom');
            // The old `external` type always opened a new tab; keep that promise.
            update_post_meta($postId, ClientPostType::META_LINK_NEW_TAB, true);
        }

        if ($subtitle !== '' && peregoAppendClientSubtitle($client, $subtitle)) {
            $contentMoved++;
        }

        foreach (PEREGO_CLIENT_LEGACY_KEYS as $key) {
            delete_post_meta($postId, $key);
        }

        $migrated++;
    }
} while (count($clients) === PEREGO_CLIENT_BATCH);

WP_CLI::success(sprintf(
    '%s%d client(s) migrated, %d already done, %d left inheriting, %d subtitle(s) moved into post content.',
    $dryRun ? '[dry-run] ' : '',
    $migrated,
    $skipped,
    $untouched,
    $contentMoved
));

/**
 * Move a client's retired subtitle into its post content as a normal paragraph block.
 *
 * Skips a post whose content already contains the text, so a partially-completed run cannot duplicate
 * it. Returns whether the content was changed.
 */
function peregoAppendClientSubtitle(WP_Post $client, string $subtitle): bool
{
    if (str_contains($client->post_content, $subtitle)) {
        return false;
    }

    $paragraph = sprintf(
        "<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->",
        esc_html($subtitle)
    );

    $content = trim($client->post_content);
    $content = $content === '' ? $paragraph : $content . "\n\n" . $paragraph;

    $result = wp_update_post([
        'ID' => $client->ID,
        'post_content' => $content,
    ], true);

    if (is_wp_error($result)) {
        WP_CLI::warning(sprintf('#%d: could not move the subtitle — %s', $client->ID, $result->get_error_message()));

        return false;
    }

    return true;
}
