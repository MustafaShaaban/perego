<?php

/**
 * Global Sections migration tool (spec 009). Modes (via MIGRATE_GS_MODE, default dry-run):
 *   - `dry-run`      : read-only inventory of every `perego_section` record; writes NOTHING to the DB.
 *   - `backup-check` : verify a non-empty DB export exists (MIGRATE_GS_BACKUP) — the rollback gate.
 *   - `apply`        : destructively remove all `perego_section` records, gated on a verified backup AND
 *                      the perego-theme/footer-careers block being registered (its migrated home).
 * Idempotent: a second `apply` is a no-op. This script is retained after the CPT/code removal (T011) as
 * the migration's rollback + orphan-report record.
 *
 * Why: only the `footer-careers` role was rendered on the public frontend (now migrated to the
 * perego-theme/footer-careers block); the other roles were seeded-but-unconsumed. The dry-run proved,
 * against the DB, exactly which records existed per role/locale, so removal caused no content loss.
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

// The former GlobalSectionPostType is removed (spec 009 T011); its identifiers are inlined here so this
// script survives as the migration's idempotent rollback/orphan-report record.
const MIGRATE_GS_POST_TYPE = 'perego_section';
const MIGRATE_GS_META_ROLE = '_perego_section_role';

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
        $backup = getenv('MIGRATE_GS_BACKUP');
        migrate_gs_apply($backup === false ? '' : (string) $backup);
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
    if (! post_type_exists(MIGRATE_GS_POST_TYPE)) {
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
        'post_type' => MIGRATE_GS_POST_TYPE,
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
        $role = (string) get_post_meta($id, MIGRATE_GS_META_ROLE, true);
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
        'post_type' => MIGRATE_GS_POST_TYPE,
        'total_records' => count($records),
        'consumed_roles' => MIGRATE_GS_CONSUMED_ROLES,
        'records' => $records,
        'anomalies' => $anomalies,
        'writes_performed' => 0,
    ];

    $path = migrate_gs_write_report($report, 'dry-run');

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
        $anomalies[] = sprintf('%d record(s) have no %s meta (unclassifiable).', $count, MIGRATE_GS_META_ROLE);
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
 * Destructively remove every perego_section record after the safety gates pass. Gates (all required):
 *   1. A verified DB backup (MIGRATE_GS_BACKUP) — the rollback path.
 *   2. The perego-theme/footer-careers block is registered — proof the one consumed role has a new home,
 *      so deletion loses no frontend content.
 * Deletes posts with force (removing their post meta and term relationships). Idempotent: a second run
 * finds no records and makes no change. Writes a final orphan report.
 */
function migrate_gs_apply(string $backupPath): void
{
    if (! post_type_exists(MIGRATE_GS_POST_TYPE)) {
        WP_CLI::success('perego_section is not registered — nothing to remove (idempotent no-op).');

        return;
    }

    // Gate 1: verified backup.
    if ($backupPath === '' || ! is_readable($backupPath) || (int) filesize($backupPath) <= 0) {
        WP_CLI::error('apply refused: set MIGRATE_GS_BACKUP to a verified, non-empty DB export first.');
    }

    // Gate 2: the migrated home must exist so no content is lost.
    $registry = class_exists('WP_Block_Type_Registry') ? WP_Block_Type_Registry::get_instance() : null;
    if ($registry === null || ! $registry->is_registered('perego-theme/footer-careers')) {
        WP_CLI::error('apply refused: perego-theme/footer-careers block is not registered (migration target missing).');
    }

    $ids = get_posts([
        'post_type' => MIGRATE_GS_POST_TYPE,
        'post_status' => 'any',
        'numberposts' => 200,
        'fields' => 'ids',
        'no_found_rows' => true,
        'suppress_filters' => false,
    ]);

    $deleted = [];
    $failed = [];
    foreach ((array) $ids as $id) {
        $id = (int) $id;
        $role = (string) get_post_meta($id, MIGRATE_GS_META_ROLE, true);
        $result = wp_delete_post($id, true);
        if ($result === false || $result === null) {
            $failed[] = $id;
            continue;
        }
        $deleted[] = ['id' => $id, 'role' => $role];
    }

    // Orphan scan: any post meta or posts of this type that survived deletion.
    $remaining = get_posts([
        'post_type' => MIGRATE_GS_POST_TYPE,
        'post_status' => 'any',
        'numberposts' => 50,
        'fields' => 'ids',
        'no_found_rows' => true,
        'suppress_filters' => false,
    ]);

    global $wpdb;
    $orphanMeta = (int) $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", MIGRATE_GS_META_ROLE)
    );

    $report = [
        'generated_at' => gmdate('c'),
        'mode' => 'apply',
        'backup' => $backupPath,
        'deleted_count' => count($deleted),
        'deleted' => $deleted,
        'failed' => $failed,
        'remaining_posts' => count((array) $remaining),
        'orphan_role_meta_rows' => $orphanMeta,
    ];
    $path = migrate_gs_write_report($report, 'apply');

    foreach ($deleted as $d) {
        WP_CLI::log(sprintf('  deleted #%d (role=%s)', $d['id'], $d['role'] === '' ? '(none)' : $d['role']));
    }
    if ($failed !== []) {
        WP_CLI::warning('Failed to delete: ' . implode(',', $failed));
    }
    if (count((array) $remaining) > 0 || $orphanMeta > 0) {
        WP_CLI::warning(sprintf('Orphans remain: %d posts, %d role-meta rows.', count((array) $remaining), $orphanMeta));
    }

    WP_CLI::success(sprintf(
        'Removed %d perego_section record(s). Report: %s',
        count($deleted),
        $path
    ));
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
function migrate_gs_write_report(array $report, string $label): string
{
    $dir = __DIR__ . '/output';
    if (! is_dir($dir)) {
        wp_mkdir_p($dir);
    }

    $safeLabel = preg_replace('/[^a-z0-9\-]/', '', $label) ?: 'report';
    $path = $dir . '/global-sections-' . $safeLabel . '-' . gmdate('Ymd-His') . '.json';
    file_put_contents($path, (string) wp_json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}
