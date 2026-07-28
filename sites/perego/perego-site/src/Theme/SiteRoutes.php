<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Theme;

defined('ABSPATH') || exit;

/**
 * Site paths that more than one renderer names.
 *
 * WHY A CONSTANT AND NOT A LITERAL — this is not tidiness, it is a correctness constraint.
 * {@see \PeregoSite\Language\PolylangLanguageDriver::localizedUrl()} resolves a path by calling
 * `url_to_postid( home_url( $path ) )` and then returning that page's *translation* permalink. A path
 * therefore only works while it matches the English page's real permalink: the moment the slug changes,
 * any literal left behind stops resolving, the driver falls through to `languagePrefixedUrl()`, and the
 * Arabic site quietly links to a URL that does not exist.
 *
 * That failure is silent — the page still renders, the link just 404s in one language — so the route has
 * exactly one definition. Renaming the page means changing the slug here and in the database together.
 */
final class SiteRoutes
{
    /**
     * The project-brief page (post 57 EN / 58 AR, "Start a Project"). Renamed from `/contact` on
     * 2026-07-26: the site has no "Contact us" page — the header's Contact Us item is an anchor to the
     * footer — so the URL now matches the page it actually serves. `PeregoSite\Theme\LegacyRouteRedirect`
     * keeps the old path working.
     */
    public const START_PROJECT = '/start-a-project';

    /** The old path, kept only so the redirect and its test name the same string. */
    public const START_PROJECT_LEGACY = '/contact';
}
