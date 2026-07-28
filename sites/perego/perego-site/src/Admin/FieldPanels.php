<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Admin;

defined('ABSPATH') || exit;

/**
 * Enqueues the Perego post-type field panels into the block editor (spec 021 Phase 4 / T019).
 *
 * The panels replace four classic meta boxes — `PostMetaBoxes`, `ProjectGalleryMetaBox`,
 * `ClientMediaMetaBox` and `ServicePortfolioMetaBox` — with typed, grouped controls in the document
 * sidebar. Because every field is registered post meta with `show_in_rest`, the panels read and write
 * through the REST entity: **no nonce, no save handler, no page reload**, and no meta key changes, so
 * the public output cannot move.
 *
 * The bundle is built from `src/EditorPanels/` by `npm run build:panels` — its own wp-scripts entry,
 * because the block build only compiles `src/Blocks`.
 */
final class FieldPanels
{
    private const HANDLE = 'perego-field-panels';

    public function register(): void
    {
        add_action('enqueue_block_editor_assets', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        // __DIR__ is src/Admin, so two levels up is the plugin root — the same root `blockDir()` uses.
        $pluginRoot = dirname(__DIR__, 2);
        $base = $pluginRoot . '/build/EditorPanels/';
        $asset = $base . 'index.asset.php';

        // The panels are optional chrome: if the bundle has not been built, the editor still works.
        if (! is_readable($asset)) {
            return;
        }

        /** @var array{dependencies: list<string>, version: string} $meta */
        $meta = require $asset;
        $url = plugins_url('build/EditorPanels/', $pluginRoot . '/perego-site.php');

        wp_enqueue_script(self::HANDLE, $url . 'index.js', $meta['dependencies'], $meta['version'], true);
        wp_set_script_translations(self::HANDLE, 'perego-site');

        if (is_readable($base . 'style-index.css')) {
            wp_enqueue_style(self::HANDLE, $url . 'style-index.css', [], $meta['version']);
        }
    }
}
