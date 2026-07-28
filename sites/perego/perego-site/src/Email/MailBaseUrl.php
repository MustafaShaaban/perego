<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

/**
 * The public base URL every email asset and link is built from.
 *
 * An email is read somewhere else, later, by someone who is not on this network — so a URL that
 * resolves for the request that sent the mail is not good enough. Two concrete failures made this
 * necessary (client report 2026-07-27): the logo was emitted as
 * `http://perego.local/wp-content/plugins/perego-site/assets/email/logo-full.png` and every CTA button
 * pointed at `http://perego.local/...`, because both were derived from the running site URL.
 *
 * Resolution order:
 *   1. the `PEREGO_MAIL_BASE_URL` constant — for wp-config-level control;
 *   2. the `perego_mail_base_url` option — so the production URL can be set from a local install to
 *      preview real mail;
 *   3. `get_option('siteurl')`.
 *
 * Steps 1 and 2 are **deliberate pins**, not defaults: while either is set, every link and asset in
 * every email points at that host regardless of where the mail was actually sent from. That is what
 * they are for, and it is also how a local install ended up mailing `https://peregoads.com` links for
 * files that only exist on `perego.local` (client report 2026-07-27, "why are links navigating me to
 * peregoads.com"). Unset both to get the normal behaviour — links follow the sending site. Production
 * needs no configuration at all, because there step 3 already *is* the real domain.
 *
 * Step 3 deliberately reads the **option** rather than calling `site_url()`/`home_url()`. Those honour
 * the `WP_SITEURL`/`WP_HOME` constants, and `wp/wp-config.php` rewrites them to the ngrok host whenever
 * a request arrives through a tunnel — which would bake an ephemeral hostname into a permanent email.
 * The option is the stable, DB-backed identity of the site, so on production it is the real domain with
 * no configuration at all.
 */
final class MailBaseUrl
{
    public const CONSTANT = 'PEREGO_MAIL_BASE_URL';

    public const OPTION = 'perego_mail_base_url';

    /**
     * Last resort, for the case where none of the three sources can answer (WordPress not loaded, or a
     * site with no `siteurl` row). A relative or empty base would render CTA links as `/work`, which no
     * mail client can follow — so this has to be an absolute public URL, not ''.
     */
    public const FALLBACK = 'https://peregoads.com';

    /** The public base URL, without a trailing slash. */
    public static function resolve(): string
    {
        if (defined(self::CONSTANT)) {
            $configured = self::normalize((string) constant(self::CONSTANT));
            if ($configured !== '') {
                return $configured;
            }
        }

        if (function_exists('get_option')) {
            $option = self::normalize((string) get_option(self::OPTION, ''));
            if ($option !== '') {
                return $option;
            }

            $siteUrl = self::normalize((string) get_option('siteurl', ''));
            if ($siteUrl !== '') {
                return $siteUrl;
            }
        }

        return self::FALLBACK;
    }

    /**
     * Move a URL that WordPress built for the current request onto the public base.
     *
     * Rather than rebuilding paths by hand — which would break under a subdirectory install or a moved
     * `wp-content` — this keeps whatever WordPress produced (`plugins_url()`, `home_url()`, …) and swaps
     * only the origin. `site_url()` is the right needle precisely *because* it reflects the tunnel: when
     * the request came through ngrok, the URL to fix carries the ngrok origin too, so they still match.
     */
    public static function rebase(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        $base = self::resolve();
        $current = function_exists('site_url') ? self::normalize((string) site_url()) : '';
        if ($current === '' || $current === $base) {
            return $url;
        }

        return str_starts_with($url, $current) ? $base . substr($url, strlen($current)) : $url;
    }

    private static function normalize(string $url): string
    {
        $url = trim($url);

        return $url === '' ? '' : rtrim($url, '/');
    }
}
