<?php

/**
 * Import the collected website logos, fix the portfolio order, and add the two projects that were
 * never imported.
 *
 * Run once, after `node scripts/fetch-portfolio-logos.mjs --out <dir>`:
 *
 *     php scripts/import-portfolio-logos.php --logos <dir> [--dry-run]
 *
 * Idempotent by design — it is a content migration that will be run more than once as logos are
 * added by hand. A project that already has a logo attachment keeps it; ordering and role captions
 * are simply re-asserted.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$options = getopt('', ['logos:', 'dry-run']);
$logoDir = rtrim((string) ($options['logos'] ?? ''), '/\\');
$dryRun = array_key_exists('dry-run', $options);

if ($logoDir === '' || ! is_dir($logoDir)) {
    exit("Pass --logos <dir> pointing at the fetch script's output.\n");
}

$_SERVER['HTTP_HOST'] = 'perego.local';
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/../wp/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * The client's list, in the client's order. `order` becomes `menu_order`, which is what the grid
 * sorts by — the previous date-descending order was the order the importer happened to run in.
 *
 * `role` is the one qualifier the client asked for: e& was a framework upgrade participation, not a
 * project Perego owned, and the card has to say so.
 */
const SITES = [
    ['order' => 1, 'slug' => 'bullion-trading-center', 'name' => 'Bullion Trading Center', 'url' => 'https://bulliontradingcenter.com/'],
    ['order' => 2, 'slug' => 'etcc', 'name' => 'Emirati Talent Competitiveness Council', 'url' => 'https://www.etcc.gov.ae/'],
    ['order' => 3, 'slug' => 'nafis-award', 'name' => 'Nafis Award', 'url' => 'https://nafisaward.etcc.gov.ae/'],
    ['order' => 4, 'slug' => 'enrra', 'name' => 'Egyptian Nuclear and Radiological Regulatory Authority', 'url' => 'https://enrra.org/'],
    ['order' => 5, 'slug' => 'moe-lessons', 'name' => 'Ministry of Education Lessons Platform', 'url' => 'https://lessons.moe.gov.eg/'],
    ['order' => 6, 'slug' => 'oshco', 'name' => 'Olayan Saudi Holding Company — OSHCO', 'url' => 'https://www.oshco.com/'],
    ['order' => 7, 'slug' => 'blackstone-eit', 'name' => 'BlackStone eIT', 'url' => 'https://blackstoneeit.com/'],
    ['order' => 8, 'slug' => 'quanta-egypt', 'name' => 'Quanta Egypt', 'url' => 'https://quanta-egypt.com/'],
    ['order' => 9, 'slug' => 'hdc-global', 'name' => 'HDC Global', 'url' => 'https://hdc-global.com/'],
    ['order' => 10, 'slug' => 'port-said-university', 'name' => 'Port Said University', 'url' => 'https://psu.edu.eg/'],
    ['order' => 11, 'slug' => 'rocc', 'name' => 'ROCC', 'url' => 'https://rocc.com/'],
    ['order' => 12, 'slug' => 'redcon-properties', 'name' => 'Redcon Properties', 'url' => 'https://www.redconproperties.com/'],
    ['order' => 13, 'slug' => 'wis-international', 'name' => 'WIS International', 'url' => 'https://wisintl.com/'],
    ['order' => 14, 'slug' => 'eand', 'name' => 'e& / Etisalat UAE', 'url' => 'https://www.eand.ae/', 'role' => 'Framework upgrade participation'],
    ['order' => 15, 'slug' => 'nama-women', 'name' => 'NAMA Women Advancement', 'url' => 'https://namawomen.ae/'],
    ['order' => 16, 'slug' => 'our-forum', 'name' => 'Our Forum', 'url' => 'https://ourforum.ae/'],
    ['order' => 17, 'slug' => 'cultural-office', 'name' => 'Cultural Office', 'url' => 'https://culturaloffice.ae/'],
    ['order' => 18, 'slug' => 'rubu-qarn', 'name' => 'Rubu’ Qarn', 'url' => 'https://rqsharjah.ae/'],
    ['order' => 19, 'slug' => 'sharjah-youth', 'name' => 'Sharjah Youth', 'url' => 'https://shjyouth.ae/'],
    ['order' => 20, 'slug' => 'reyada-center', 'name' => 'Reyada Center', 'url' => 'https://reyadacenter.ae/'],
    ['order' => 21, 'slug' => 'hpd', 'name' => 'Health Promotion Department', 'url' => 'https://hpd.ae/'],
    ['order' => 22, 'slug' => 'sheikh-sultan-award', 'name' => 'Sheikh Sultan Award for Celebrating the Spirit of Youth', 'url' => 'https://sheikhsultanaward.ae/'],
    ['order' => 23, 'slug' => 'children-of-sharjah', 'name' => 'Children of Sharjah', 'url' => 'https://shjch.ae/'],
    ['order' => 24, 'slug' => 'sharjah-womens-sports', 'name' => 'Sharjah Women’s Sports', 'url' => 'https://www.sws.gov.ae/'],
    ['order' => 25, 'slug' => 'fann-media', 'name' => 'FANN Media Discovery Platform', 'url' => 'https://fannmedia.ae/'],
    ['order' => 26, 'slug' => 'child-safety', 'name' => 'Child Safety Department', 'url' => 'https://childsafety.gov.ae/'],
    ['order' => 27, 'slug' => 'sharjah-cd', 'name' => 'Sharjah Capability Development', 'url' => 'https://sharjahcd.ae/'],
    ['order' => 28, 'slug' => 'sajaya', 'name' => 'Sajaya Young Ladies of Sharjah', 'url' => 'https://sajaya.ae/'],
    ['order' => 29, 'slug' => 'sharjah-olympic-center', 'name' => 'Sharjah Olympic Center for Women’s Sports', 'url' => 'https://oc.sws.gov.ae/'],
];

/**
 * Both language posts for a site, keyed by locale, matched on the live URL.
 *
 * The URL is the identity here rather than the title: titles were imported verbatim from the
 * client's list and two of them carry typographic quotes that do not survive a round trip through
 * every tool that has touched this data.
 *
 * @return array<string,int>
 */
function projectsFor(string $url): array
{
    $normalize = static fn (string $value): string => rtrim(strtolower($value), '/');

    $found = [];
    foreach (get_posts([
        'post_type' => 'perego_project',
        'post_status' => 'any',
        // Bounded, never -1: the portfolio is 29 sites in two languages, and an unbounded query
        // is a habit that eventually meets a table that does not fit in memory.
        'numberposts' => 200,
        'lang' => '',
        'meta_key' => '_perego_site_url',
    ]) as $post) {
        if ($normalize((string) get_post_meta($post->ID, '_perego_site_url', true)) !== $normalize($url)) {
            continue;
        }
        $language = function_exists('pll_get_post_language') ? (string) pll_get_post_language($post->ID) : 'en';
        $found[$language ?: 'en'] = $post->ID;
    }

    return $found;
}

/** Create the EN/AR pair for a site the previous import skipped, linked through Polylang. */
function createPair(array $site, bool $dryRun): array
{
    if ($dryRun) {
        return [];
    }

    $ids = [];
    foreach (['en', 'ar'] as $language) {
        $id = wp_insert_post([
            'post_type' => 'perego_project',
            'post_status' => 'publish',
            'post_title' => $site['name'],
            'post_name' => $site['slug'] . ($language === 'ar' ? '-ar' : ''),
            'menu_order' => $site['order'],
        ], true);

        if (is_wp_error($id)) {
            return [];
        }

        update_post_meta($id, '_perego_site_url', esc_url_raw($site['url']));
        update_post_meta($id, '_perego_client', $site['name']);
        update_post_meta($id, '_perego_site_type', 'corporate');
        // The `web-ar` term is the Arabic translation of `web`; the grid filters on the localized
        // term, so an AR post carrying the EN term would vanish from the Arabic archive.
        wp_set_object_terms($id, $language === 'ar' ? 'web-ar' : 'web', 'perego_project_category');

        if (function_exists('pll_set_post_language')) {
            pll_set_post_language($id, $language);
        }
        $ids[$language] = $id;
    }

    if (count($ids) === 2 && function_exists('pll_save_post_translations')) {
        pll_save_post_translations($ids);
    }

    return $ids;
}

/** Sideload one logo file into the media library, returning its attachment id. */
function importLogo(string $file, string $name, bool $dryRun): int
{
    if ($dryRun) {
        return 0;
    }

    $contents = file_get_contents($file);
    if ($contents === false) {
        return 0;
    }

    $upload = wp_upload_bits(basename($file), null, $contents);
    if (! empty($upload['error'])) {
        return 0;
    }

    $type = wp_check_filetype(basename($file), null);
    $id = wp_insert_attachment([
        'post_mime_type' => (string) ($type['type'] ?: 'image/png'),
        'post_title' => $name . ' — logo',
        'post_status' => 'inherit',
    ], $upload['file']);

    if (is_wp_error($id) || $id === 0) {
        return 0;
    }

    // An SVG has no intermediate sizes to generate, and asking for them logs a warning.
    if (($type['type'] ?? '') !== 'image/svg+xml') {
        wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $upload['file']));
    }
    update_post_meta($id, '_wp_attachment_image_alt', $name);

    return (int) $id;
}

$logoFiles = [];
foreach ((array) glob($logoDir . '/*.{png,jpg,jpeg,webp,svg}', GLOB_BRACE) as $path) {
    if (preg_match('/^(\d{2})-/', basename((string) $path), $match) === 1) {
        $logoFiles[(int) $match[1]] = (string) $path;
    }
}

$report = ['linked' => 0, 'created' => 0, 'logos' => 0, 'kept' => 0, 'noLogo' => []];

foreach (SITES as $site) {
    $posts = projectsFor($site['url']);

    if ($posts === []) {
        $posts = createPair($site, $dryRun);
        if ($posts !== []) {
            $report['created']++;
            printf("created  %2d. %s (EN %d / AR %d)\n", $site['order'], $site['name'], $posts['en'] ?? 0, $posts['ar'] ?? 0);
        } else {
            printf("SKIP     %2d. %s — not in the database and could not be created\n", $site['order'], $site['name']);
            continue;
        }
    }

    $logoId = 0;
    foreach ($posts as $postId) {
        $existing = (int) get_post_meta($postId, '_perego_logo_id', true);
        if ($existing > 0 && get_post($existing) !== null) {
            $logoId = $existing;
        }
    }

    $hasFile = isset($logoFiles[$site['order']]);

    if ($logoId === 0 && $hasFile) {
        $logoId = importLogo($logoFiles[$site['order']], $site['name'], $dryRun);
        if ($logoId > 0) {
            $report['logos']++;
        }
    } elseif ($logoId > 0) {
        $report['kept']++;
    }

    // A dry run never writes an attachment, so judge it on the collected FILE rather than on the
    // id — otherwise the preview reports every site as logo-less and says nothing useful.
    $willHaveLogo = $logoId > 0 || ($dryRun && $hasFile);

    if (! $willHaveLogo) {
        $report['noLogo'][] = sprintf('%2d. %s (%s)', $site['order'], $site['name'], $site['url']);
    }

    if (! $dryRun) {
        foreach ($posts as $postId) {
            wp_update_post(['ID' => $postId, 'menu_order' => $site['order']]);
            if ($logoId > 0) {
                update_post_meta($postId, '_perego_logo_id', $logoId);
            }
            if (isset($site['role'])) {
                update_post_meta($postId, '_perego_role', $site['role']);
            }
        }
    }

    $report['linked']++;
    printf(
        "%-8s %2d. %-52s %s\n",
        $willHaveLogo ? 'ok' : 'NO LOGO',
        $site['order'],
        mb_substr($site['name'], 0, 50),
        $logoId > 0 ? "logo #{$logoId}" : ($willHaveLogo ? 'logo ready to import' : 'renders as a name plate'),
    );
}

printf(
    "\n%d projects ordered, %d created, %d logos imported, %d already had one.\n",
    $report['linked'],
    $report['created'],
    $report['logos'],
    $report['kept'],
);

if ($report['noLogo'] !== []) {
    printf("\nStill needs a logo uploaded by hand (%d):\n  %s\n", count($report['noLogo']), implode("\n  ", $report['noLogo']));
}
