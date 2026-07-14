<?php

/**
 * Global Sections migration reporter (spec 009). SAFE BY DEFAULT: the only mode implemented here is a
 * read-only **dry run** that inventories every `perego_section` record and produces a report — it writes
 * NOTHING to the database. A `backup-check` mode verifies a DB export exists (the gate for a future
 * destructive `apply`, which is intentionally NOT implemented in this commit — see spec 009 T010).
 *
 * Why: only the `footer-careers` role is actually rendered on the public frontend
 * (SiteFooterRenderer::careersEditorial); the other roles are seeded-but-unconsumed. This report proves,
 * against the LIVE database, exactly which records exist per role/locale before anything is migrated or
 * removed, so removal causes no content loss.
 *
 * Run (from repo root; `require` keeps `declare(strict_types)` valid, which `wp eval-file` breaks):
 *   wp eval 'require "sites/perego/perego-site/scripts/migrate-global-sections.php";' --path=wp
 * Mode + args are passed via environment variables so the `require` invocation stays clean:
 *   MIGRATE_GS_MODE=backup-check MIGRATE_GS_BACKUP=/path/to/dump.sql wp eval 'require "...";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through `wp eval 'require ...'`.\n");
    exit(1);
}

use PeregoSite\PostTypes\GlobalSectionPostType;

/** Roles whose content is rendered on the public frontend (must have a migration target before removal). */
const MIGRATE_GS_CONSUMED_ROLES = ['footer-careers'];

$mode = getenv('MIGRATE_GS_MODE');
$mode = ($mode === false || $mode === '') ? 'dry-run' : (string) $mode;

switch ($mode) {
    case 'dry-run':
        migrate_gs_dry_run();
        break;
    case 'backup-check':
        $backup = getenv('MIGRATE_GS_BACKUP');
        migrate_gs_backup_check($backup === false ? '' : (string) $backup);
        break;
    case 'apply':
        WP_CLI::error(
            'apply is not implemented in this commit. Destructive removal is spec 009 T010 and requires '
            . 'a passed backup-check plus a verified footer-careers editable surface (T009). Aborting.'
        );
        break;
    default:
        WP_CLI::error(sprintf('Unknown mode "%s". Use: dry-run | backup-check | apply.', $mode));
}

/**
 * Read-only inventory of every perego_section record. Writes a timestamped JSON report to
 * scripts/output/ and prints a human summary. Never modifies the database.
 */
function migrate_gs_dry_run(): void
{
    if (! post_type_exists(GlobalSectionPostType::POST_TYPE)) {
        WP_CLI::success(
            'perego_section is not registered — the CPT appears already removed. Nothing to inventory.'
        );

        return;
    }

    $pllActive = function_exists('pll_get_post_language');
    if (! $pllActive) {
        WP_CLI::warning('Polylang inactive — locale will report "unknown" for every record.');
    }

    $ids = get_posts([
        'post_type' => GlobalSectionPostType::POST_TYPE,
        'post_status' => 'any',
        'numberposts' => 200,
        'fields' => 'ids',
        'no_found_rows' => true,
        'suppress_filters' => false,
    ]);

    $records = [];
    /** @var array<string, array<string, list<int>>> $byRoleLocale role => locale => [ids] */
    $byRoleLocale = [];

    foreach ((array) $ids as $id) {
        $id = (int) $id;
        $role = (string) get_post_meta($id, GlobalSectionPostType::META_ROLE, true);
        $locale = $pllActive ? (string) pll_get_post_language($id) : 'unknown';
        $locale = $locale === '' ? 'unknown' : $locale;
        $post = get_post($id);

        $records[] = [
            'id' => $id,
            'role' => $role,
            'locale' => $locale,
            'status' => $post ? $post->post_status : 'missing',
            'title' => $post ? $post->post_title : '',
            'consumed' => in_array($role, MIGRATE_GS_CONSUMED_ROLES, true),
        ];

        $byRoleLocale[$role][$locale][] = $id;
    }

    $anomalies = migrate_gs_anomalies($byRoleLocale, $pllActive);

    $report = [
        'generated_at' => gmdate('c'),
        'post_type' => GlobalSectionPostType::POST_TYPE,
        'total_records' => count($records),
        'consumed_roles' => MIGRATE_GS_CONSUMED_ROLES,
        'records' => $records,
        'anomalies' => $anomalies,
        'writes_performed' => 0,
    ];

    $path = migrate_gs_write_report($report);

    WP_CLI::log(sprintf('Global Sections dry run — %d record(s):', count($records)));
    foreach ($records as $r) {
        WP_CLI::log(sprintf(
            '  #%d  role=%-16s locale=%-8s status=%-8s consumed=%s',
            $r['id'],
            $r['role'] === '' ? '(none)' : $r['role'],
            $r['locale'],
            $r['status'],
            $r['consumed'] ? 'YES' : 'no'
        ));
    }

    if ($anomalies !== []) {
        WP_CLI::warning('Anomalies found — resolve before any apply:');
        foreach ($anomalies as $a) {
            WP_CLI::warning('  - ' . $a);
        }
    }

    WP_CLI::success(sprintf('Report written to %s (no database changes made).', $path));
}

/**
 * Detect conditions that must be resolved before a destructive apply.
 *
 * @param array<string, array<string, list<int>>> $byRoleLocale
 * @return list<string>
 */
function migrate_gs_anomalies(array $byRoleLocale, bool $pllActive): array
{
    $anomalies = [];

    // Every consumed role must have a migration target for both languages before removal.
    foreach (MIGRATE_GS_CONSUMED_ROLES as $role) {
        foreach (['en', 'ar'] as $locale) {
            $present = isset($byRoleLocale[$role][$locale]) && $byRoleLocale[$role][$locale] !== [];
            if ($pllActive && ! $present) {
                $anomalies[] = sprintf('Consumed role "%s" is missing a "%s" record.', $role, $locale);
            }
        }
    }

    // A record with no role meta cannot be classified.
    if (isset($byRoleLocale[''])) {
        $count = 0;
        foreach ($byRoleLocale[''] as $ids) {
            $count += count($ids);
        }
        $anomalies[] = sprintf('%d record(s) have no %s meta (unclassifiable).', $count, GlobalSectionPostType::META_ROLE);
    }

    // Duplicate singletons (more than one record for the same role+locale).
    foreach ($byRoleLocale as $role => $locales) {
        foreach ($locales as $locale => $ids) {
            if (count($ids) > 1) {
                $anomalies[] = sprintf(
                    'Role "%s"/%s has %d records (%s) — singleton expected.',
                    $role === '' ? '(none)' : $role,
                    $locale,
                    count($ids),
                    implode(',', $ids)
                );
            }
        }
    }

    return $anomalies;
}

/**
 * Verify a non-empty DB export exists at the given path. This is the hard gate for a future apply; it
 * performs no database work itself.
 */
function migrate_gs_backup_check(string $path): void
{
    if ($path === '') {
        WP_CLI::error('Pass the DB export path: backup-check /path/to/dump.sql');
    }
    if (! is_readable($path)) {
        WP_CLI::error(sprintf('No readable DB export at %s. Create one before apply.', $path));
    }
    $size = (int) filesize($path);
    if ($size <= 0) {
        WP_CLI::error(sprintf('DB export at %s is empty (%d bytes).', $path, $size));
    }

    WP_CLI::success(sprintf('Backup gate OK: %s (%d bytes).', $path, $size));
}

/**
 * Persist the report as JSON under scripts/output/. Returns the written path.
 *
 * @param array<string, mixed> $report
 */
function migrate_gs_write_report(array $report): string
{
    $dir = __DIR__ . '/output';
    if (! is_dir($dir)) {
        wp_mkdir_p($dir);
    }

    $path = $dir . '/global-sections-dry-run-' . gmdate('Ymd-His') . '.json';
    file_put_contents($path, (string) wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}
