<?php

/**
 * One-time retrofit (spec 021): the header's "Contact Us" item points at the Contact page instead of
 * the footer anchor the handoff intends.
 *
 * `SiteHeaderRenderer::seedNavItems()` has always seeded `#contact` — every page's footer carries
 * `id="contact"`, and the Contact page is reached through the "Start a Project" CTAs. But the seed only
 * applies while the block has no stored attributes, and the editor-side seed in
 * `src/Blocks/site-header/preview.js` had drifted to `/contact`. The first Site Editor save wrote that
 * drifted value into the `header` template part, in both languages, so the live nav has sent visitors to
 * the Contact page ever since. The JS seed is fixed; this migrates the already-saved data, which is what
 * carries the fix to staging/production, where the same value is saved. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-header-contact-anchor.php";' --path=wp
 *
 * Idempotent: only a top-level item whose href is the Contact page is rewritten, and only to `#contact`,
 * so a second run reports 0. Labels, ordering, children, and every other item are left untouched.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

/** The Contact-page hrefs this retrofit replaces, in every shape the editor may have stored. */
$contactPageHrefs = ['/contact', '/contact/', 'contact', '/contact-2', '/contact-2/'];

/** The same-page footer anchor, matching `SiteHeaderRenderer::seedNavItems()`. */
$contactAnchor = '#contact';

/**
 * Rewrites the Contact item's href in one decoded nav list.
 *
 * A top-level item only: the Services dropdown's children are service links, never the Contact link.
 *
 * @param list<array<string, mixed>> $items
 * @return array{items: list<array<string, mixed>>, changed: int}
 */
$rewriteNav = static function (array $items) use ($contactPageHrefs, $contactAnchor): array {
    $changed = 0;

    foreach ($items as $index => $item) {
        // A hand-edited nav list can hold anything; only a real item has an href to compare.
        if (! is_array($item)) {
            continue;
        }

        $href = trim((string) ($item['href'] ?? ''));

        if (! in_array($href, $contactPageHrefs, true)) {
            continue;
        }

        $items[$index]['href'] = $contactAnchor;
        $changed++;
    }

    return ['items' => $items, 'changed' => $changed];
};

/**
 * Rewrites one `navItemsEn`/`navItemsAr` JSON string, answering with the original string and a zero
 * count when there is nothing to do — so a block comment's bytes only move when a href genuinely
 * changed, and a re-run is a no-op.
 *
 * @return array{json: string, changed: int}
 */
$rewriteNavJson = static function (string $json) use ($rewriteNav): array {
    $unchanged = ['json' => $json, 'changed' => 0];
    $decoded = json_decode($json, true);

    if (! is_array($decoded) || $decoded === []) {
        return $unchanged;
    }

    $result = $rewriteNav($decoded);

    if ($result['changed'] === 0) {
        return $unchanged;
    }

    // JSON_UNESCAPED_UNICODE keeps the Arabic labels readable and the slashes flag keeps the service
    // paths as stored, so the re-encoded value differs from the original in the href alone.
    $encoded = wp_json_encode($result['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // wp_json_encode() answers false on malformed UTF-8. Never write a broken attribute: report no
    // change so the part is left exactly as it was, for a human to look at.
    if (! is_string($encoded)) {
        return $unchanged;
    }

    return ['json' => $encoded, 'changed' => $result['changed']];
};

$parts = get_posts([
    'post_type'   => 'wp_template_part',
    'post_status' => ['publish', 'draft'],
    'numberposts' => 100,
]);

$changedLinks = 0;
$changedParts = 0;

foreach ($parts as $part) {
    if ($part->post_name !== 'header') {
        continue;
    }

    $blocks = parse_blocks($part->post_content);
    $partChanges = 0;

    foreach ($blocks as $index => $block) {
        if (($block['blockName'] ?? '') !== 'perego-theme/site-header') {
            continue;
        }

        foreach (['navItemsEn', 'navItemsAr'] as $attribute) {
            $stored = (string) ($block['attrs'][$attribute] ?? '');

            if ($stored === '') {
                continue; // Never edited in this language, so the PHP seed already answers `#contact`.
            }

            $result = $rewriteNavJson($stored);

            if ($result['changed'] > 0) {
                $blocks[$index]['attrs'][$attribute] = $result['json'];
                $partChanges += $result['changed'];
            }
        }
    }

    if ($partChanges === 0) {
        continue; // Already migrated, or this header never stored a Contact-page link.
    }

    // wp_slash() is mandatory: wp_update_post() unslashes what it is given, and this content is dense
    // with `"` escapes from the block serializer. Without it every backslash is eaten, the stored
    // JSON stops parsing, and the renderer silently falls back to the PHP seed — which looks like the
    // whole nav reverting (order and Arabic labels included), not like a corrupted attribute.
    wp_update_post([
        'ID'           => $part->ID,
        'post_content' => wp_slash(serialize_blocks($blocks)),
    ]);

    $changedLinks += $partChanges;
    $changedParts++;
}

WP_CLI::success(
    "Header Contact anchor retrofit — {$changedLinks} link(s) in {$changedParts} template part(s) "
    . 'rewritten to #contact (idempotent).'
);
