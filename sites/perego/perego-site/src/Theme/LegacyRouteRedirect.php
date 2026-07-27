<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Theme;

defined('ABSPATH') || exit;

/**
 * Permanent redirects for paths that have been renamed (2026-07-26: `/contact` → `/start-a-project`).
 *
 * The query string is preserved deliberately: every service page links `?service=<slug>`, and that
 * parameter is what preselects the service on arrival. Dropping it would turn an existing link from
 * "start a project about video editing" into a blank form, which is the kind of regression a redirect is
 * supposed to prevent.
 *
 * WordPress would eventually resolve the old slug itself — `wp_old_slug_redirect()` handles a renamed
 * post — but only for the exact post that was renamed, only while the old slug stays recorded, and it
 * does not cover the Arabic translation reliably. This is explicit instead, so the behaviour is testable
 * and does not depend on post-meta housekeeping surviving a migration.
 */
final class LegacyRouteRedirect
{
    /** Old path (no trailing slash, lowercase) => new path, both site-root-relative. */
    private const MOVED = [
        '/contact' => SiteRoutes::START_PROJECT,
        '/ar/contact-2' => '/ar/start-a-project-2',
    ];

    /**
     * Resolve a request path to its replacement, or null when nothing moved.
     *
     * Pure so it can be unit-tested without WordPress: the caller supplies the path.
     */
    public function destinationFor(string $requestPath): ?string
    {
        $path = strtolower(untrailingslashit(strtok($requestPath, '?')));

        return self::MOVED[$path] ?? null;
    }

    /** Hooked on `template_redirect`, before WordPress renders a 404 for the old path. */
    public function maybeRedirect(): void
    {
        $requestUri = isset($_SERVER['REQUEST_URI'])
            ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']))
            : '';

        if ($requestUri === '') {
            return;
        }

        $destination = $this->destinationFor((string) wp_parse_url($requestUri, PHP_URL_PATH));

        if ($destination === null) {
            return;
        }

        $query = (string) wp_parse_url($requestUri, PHP_URL_QUERY);
        $target = home_url($destination) . ($query !== '' ? '?' . $query : '');

        wp_safe_redirect($target, 301);
        exit;
    }
}
