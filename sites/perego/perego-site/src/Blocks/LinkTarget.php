<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

use PeregoSite\Language\LanguageDriver;

/**
 * Resolves one editable link — a nav item, a CTA button, a footer link — to the href and the
 * target/rel attributes a renderer should emit (spec 021 T036).
 *
 * Before this, every editable link was a free-text URL box, so an editor had to know and type the
 * right path, and a link to a page whose slug later changed silently broke. A link may now name a
 * record instead of a URL, and the renderer resolves it at render time in the language being served.
 *
 * The stored shape is deliberately ADDITIVE over the old one — the plain `href` string is still the
 * custom-URL value, and the new keys sit beside it:
 *
 *     [ 'label' => …, 'href' => '/contact',
 *       'linkKind' => 'custom'|'dynamic', 'postType' => 'page', 'postId' => 42,
 *       'openInNewTab' => false ]
 *
 * so every link saved before this existed keeps resolving through exactly the path it always did, with
 * no migration. `linkKind` absent means `custom`.
 *
 * Resolution rules:
 *  - **custom** — an internal path is localized; an external URL, `mailto:`/`tel:`, or a `#anchor` is
 *    used verbatim. Lifted from `SiteHeaderRenderer::ctaHref()`, which now delegates here, so the
 *    behaviour every existing renderer test pins is unchanged.
 *  - **dynamic** — the chosen record's permalink **in the current locale**, so an Arabic page links to
 *    the Arabic record. Falls back to the custom `href` (then to the fallback path) when the record is
 *    gone or unpublished, because a deleted page must never produce a link to nowhere.
 */
final class LinkTarget
{
    public function __construct(private readonly LanguageDriver $driver)
    {
    }

    /**
     * Collect one link out of a block's flat, prefixed attributes.
     *
     * A block with a single link stores it as `ctaUrl` / `ctaLinkKind` / `ctaPostId` / `ctaOpenInNewTab`
     * rather than one object attribute, because `<prefix>Url` is then the SAME attribute the block
     * already had before the picker existed — so no block needs an attribute migration or a deprecation.
     *
     * @param array<string, mixed> $attributes the block's attributes
     * @param string               $prefix     attribute prefix, e.g. `cta` for `ctaUrl`
     * @return array<string, mixed> a link in the shape `href()` and `targetAttributes()` expect
     */
    public static function fromAttributes(array $attributes, string $prefix = 'cta'): array
    {
        return [
            'href' => (string) ($attributes[$prefix . 'Url'] ?? ''),
            'linkKind' => (string) ($attributes[$prefix . 'LinkKind'] ?? 'custom'),
            'postId' => (int) ($attributes[$prefix . 'PostId'] ?? 0),
            'openInNewTab' => (bool) ($attributes[$prefix . 'OpenInNewTab'] ?? false),
        ];
    }

    /**
     * The resolved href for a link, or the fallback when the link resolves to nothing.
     *
     * @param array<string, mixed> $link     one link's stored attributes
     * @param string               $fallback the route to use when the link is empty (e.g. '/contact')
     */
    public function href(array $link, string $fallback = '/'): string
    {
        if (($link['linkKind'] ?? 'custom') === 'dynamic') {
            $permalink = $this->driver->localizedPermalink((int) ($link['postId'] ?? 0));

            if ($permalink !== '') {
                return $permalink;
            }
        }

        return $this->customHref(trim((string) ($link['href'] ?? '')), $fallback);
    }

    /**
     * The resolved href **only when the editor actually configured this link**, otherwise an empty
     * string.
     *
     * `href()` always answers with something, which is right for a renderer that holds a language
     * driver and wants one call. But a pure renderer that is handed already-resolved data owns its own
     * default route, and resolving that route through the driver is not a no-op: `home_url('/contact')`
     * and the driver's localized permalink for the same page differ by a trailing slash. Routing an
     * unconfigured link through `href()` therefore silently rewrites URLs the block never touched — this
     * is what a curl-diff of `/work/` caught during T036. Callers in that shape use this instead, so an
     * unconfigured link changes nothing at all.
     *
     * @param array<string, mixed> $link one link's stored attributes
     */
    public function hrefIfSet(array $link): string
    {
        $isDynamic = ($link['linkKind'] ?? 'custom') === 'dynamic' && (int) ($link['postId'] ?? 0) > 0;

        if (! $isDynamic && trim((string) ($link['href'] ?? '')) === '') {
            return '';
        }

        return $this->href($link);
    }

    /**
     * The `target`/`rel` attribute string for a link — a leading space included so a renderer can
     * concatenate it straight into a tag, and an empty string when the link opens in place.
     *
     * `rel="noopener"` always accompanies `target="_blank"`, matching `SiteFooterRenderer` and
     * `ClientsCarouselRenderer`.
     *
     * @param array<string, mixed> $link one link's stored attributes
     */
    public function targetAttributes(array $link): string
    {
        return ! empty($link['openInNewTab']) ? ' target="_blank" rel="noopener"' : '';
    }

    /**
     * A custom URL. An empty value falls back to the caller's route; an external, protocol-relative,
     * mailto/tel, or same-page-anchor URL is used verbatim; only an internal path is localized, so it
     * never collapses to the homepage.
     */
    private function customHref(string $url, string $fallback): string
    {
        if ($url === '') {
            return $this->driver->localizedUrl($fallback);
        }

        if (
            str_starts_with($url, '#')
            || str_starts_with($url, 'mailto:')
            || str_starts_with($url, 'tel:')
            || (bool) preg_match('#^(https?:)?//#i', $url)
        ) {
            return $url;
        }

        return $this->driver->localizedUrl($url);
    }
}
