<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Repositories;

defined('ABSPATH') || exit;

use PeregoSite\Content\PortfolioContent;
use PeregoSite\Content\TranslatedMeta;
use PeregoSite\PostTypes\ProjectPostType;
use WP_Post;
use WP_Query;
use WP_Term;

/**
 * Reads `perego_project` posts for the portfolio grid (spec 003 / M3). Keeps the WP_Query + meta +
 * term lookups in one place so the block renderer stays a pure function of an array. The pure
 * mapping (`toGridCard()`) is unit-testable with a WP_Post-shaped fixture; the query itself is thin.
 */
final class ProjectRepository
{
    /**
     * Resolve a project's editor-managed gallery attachment IDs into safe image data. The meta is
     * intentionally a normal post-meta list: seed data supplies a default only when absent, while
     * editors retain full control thereafter.
     *
     * @return list<array{src: string, thumb: string, alt: string}>
     */
    public function galleryFor(WP_Post $project): array
    {
        $ids = $this->galleryIds($project);

        return array_values(array_filter(array_map(static function (int $id): ?array {
            $src = wp_get_attachment_image_url($id, 'full');
            if (! is_string($src) || $src === '') {
                return null;
            }

            $thumb = wp_get_attachment_image_url($id, 'large');
            $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);

            return [
                'src' => $src,
                'thumb' => is_string($thumb) && $thumb !== '' ? $thumb : $src,
                'alt' => $alt !== '' ? $alt : get_the_title($id),
            ];
        }, $ids)));
    }

    /**
     * A project's documents, as URLs the lightbox can open.
     *
     * Same English fallback as the gallery: an Arabic project carries no media of its own, and a PDF
     * is language-neutral data rather than prose. `wp_get_attachment_url()` rather than the image
     * helpers, which answer nothing for a non-image attachment.
     *
     * @return list<array{src: string, title: string}>
     */
    public function pdfsFor(WP_Post $project): array
    {
        $ids = $this->pdfIds($project);

        return array_values(array_filter(array_map(static function (int $id): ?array {
            $src = wp_get_attachment_url($id);
            if (! is_string($src) || $src === '') {
                return null;
            }

            return ['src' => $src, 'title' => (string) get_the_title($id)];
        }, $ids)));
    }

    /**
     * A project's document attachment ids, falling back to its linked EN translation's.
     *
     * @return list<int>
     */
    private function pdfIds(WP_Post $project): array
    {
        $ids = $this->normalizeGalleryIds(get_post_meta($project->ID, ProjectPostType::META_PDFS, true));
        if ($ids !== []) {
            return $ids;
        }

        $enId = $this->enTranslationId($project);

        return $enId === 0 ? [] : $this->normalizeGalleryIds(get_post_meta($enId, ProjectPostType::META_PDFS, true));
    }

    /**
     * @return array{previous: ?WP_Post, next: ?WP_Post}
     */
    public function adjacentFor(WP_Post $project): array
    {
        $projects = $this->projectsForLocale($project, 120);
        $index = array_search($project->ID, array_map(static fn (WP_Post $item): int => $item->ID, $projects), true);
        $count = count($projects);

        if (! is_int($index) || $count < 2) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $projects[($index - 1 + $count) % $count],
            'next' => $projects[($index + 1) % $count],
        ];
    }

    /**
     * @return list<WP_Post>
     */
    public function relatedFor(WP_Post $project, int $limit = 3): array
    {
        $args = $this->localeQueryArgs($project) + [
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'post__not_in' => [$project->ID],
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        $terms = get_the_terms($project->ID, ProjectPostType::TAXONOMY);
        if (is_array($terms) && $terms !== []) {
            $args['tax_query'] = [[
                'taxonomy' => ProjectPostType::TAXONOMY,
                'field' => 'term_id',
                'terms' => [(int) $terms[0]->term_id],
            ]];
        }

        $related = (new WP_Query($args))->posts;
        if (count($related) >= $limit) {
            return $related;
        }

        // Some handoff demo categories contain fewer than three projects. Preserve the three-card
        // layout by filling the remaining slots with other current-language work, never duplicating
        // the current project or an already selected related project.
        unset($args['tax_query']);
        $args['post__not_in'] = array_merge([$project->ID], array_map(static fn (WP_Post $item): int => $item->ID, $related));
        $args['posts_per_page'] = $limit - count($related);

        return array_merge($related, (new WP_Query($args))->posts);
    }

    /**
     * All published projects, newest first, shaped for PortfolioGridRenderer. Bounded to a sane cap
     * (never -1) per the query-discipline rule.
     *
     * `$featuredOnly` narrows the set to the shortlist the home page leads with; `/work` renders the
     * same block without it and still shows everything.
     *
     * @return list<array{title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string}>
     */
    public function allForGrid(PortfolioContent $content, bool $featuredOnly = false): array
    {
        // `menu_order` first, then date. The client supplied the website portfolio in a deliberate
        // order — their strongest work leads — and date DESC was really "whatever order the importer
        // happened to run in". Anything never given an explicit position keeps `menu_order` 0 and so
        // sorts ahead; that is why the tie-break is still date, and why the ordered set was numbered
        // from 1 rather than 0 (scripts/import-portfolio-logos.php).
        $query = new WP_Query([
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 120,
            'no_found_rows' => true,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        ]);

        // Filtered here rather than as a `meta_query`, because an Arabic project holds no meta of its
        // own and inherits the English record's — a meta_query would silently empty the Arabic home
        // page. The set is already capped at 120 with no_found_rows, so the pass is cheap.
        $posts = $featuredOnly
            ? array_values(array_filter($query->posts, fn (WP_Post $post): bool => $this->isFeatured($post)))
            : $query->posts;

        return array_map(
            fn (WP_Post $post): array => $this->toGridCard($post, $content),
            $posts,
        );
    }

    /**
     * Whether this project is on the home shortlist, falling back to its linked English translation.
     *
     * The empty-string test is the point, and is why {@see ProjectPostType::META_FEATURED} registers
     * no default: `get_post_meta()` answers `''` for a key never written but `'0'` for one explicitly
     * set to zero. So an Arabic project that has never been touched inherits the English answer, while
     * one deliberately taken off the shortlist stays off it rather than inheriting its way back on.
     */
    public function isFeatured(WP_Post $post): bool
    {
        $own = get_post_meta($post->ID, ProjectPostType::META_FEATURED, true);
        if ($own !== '' && $own !== false) {
            return (int) $own > 0;
        }

        $enId = $this->enTranslationId($post);

        return $enId !== 0 && (int) get_post_meta($enId, ProjectPostType::META_FEATURED, true) > 0;
    }

    /**
     * `gallerySrcs`/`videoUrl` carry the card's lightbox payload: client request 2026-07-26 replaced the
     * per-project single page with a lightbox opened from the card, so the grid needs the same media the
     * showcase cards already use (see ServiceSelectedWorkRenderer). `url` is kept — the archive route
     * still resolves, nothing links to it any more.
     *
     * `logoUrl`/`siteUrl` carry the web-card variant: client request 2026-07-27 replaced the cropped
     * screenshot on web-category cards with the client's logo, and the card links out to the live site
     * instead of opening the lightbox. Both are empty for every other category.
     *
     * `role` is the optional contribution qualifier. It exists because one project — e& / Etisalat UAE —
     * was a framework upgrade Perego took part in rather than a site it owned, and a portfolio has to
     * say so. Any project can carry one; only that card sets it today.
     *
     * `pdfSrcs` carries any documents the client supplied (owner 2026-07-28) — appended after the
     * artwork so a case study opens from the same card, without displacing what the card leads with.
     *
     * @return array{id: int, icon: string, title: string, url: string, category: string, categoryLabel: string, excerpt: string, thumbUrl: string, thumbAlt: string, gallerySrcs: list<string>, pdfSrcs: list<string>, videoUrl: string, logoUrl: string, logoAlt: string, siteUrl: string, role: string}
     */
    public function toGridCard(WP_Post $post, PortfolioContent $content): array
    {
        $terms    = get_the_terms($post->ID, ProjectPostType::TAXONOMY);
        $category = (is_array($terms) && $terms !== []) ? $this->canonicalCategorySlug($terms[0]) : '';

        $client   = (string) get_post_meta($post->ID, '_perego_client', true);
        $year     = (string) get_post_meta($post->ID, '_perego_year', true);
        $excerpt  = $this->buildExcerpt($post, $content, $client, $year);

        // AR projects carry no featured image of their own; reuse the linked EN post's (see galleryIds()).
        $thumbId  = (int) get_post_thumbnail_id($post->ID);
        if ($thumbId === 0) {
            $enId = $this->enTranslationId($post);
            $thumbId = $enId === 0 ? 0 : (int) get_post_thumbnail_id($enId);
        }
        $thumbUrl = $thumbId ? (string) wp_get_attachment_image_url($thumbId, 'large') : '';
        $thumbAlt = $thumbId ? (string) get_post_meta($thumbId, '_wp_attachment_image_alt', true) : '';

        $logoId  = $this->logoId($post);
        $logoUrl = $logoId ? (string) wp_get_attachment_image_url($logoId, 'large') : '';
        $logoAlt = $logoId ? (string) get_post_meta($logoId, '_wp_attachment_image_alt', true) : '';

        return [
            'id' => (int) $post->ID,
            'icon' => $this->iconFor($post),
            'title' => get_the_title($post),
            'url' => (string) get_permalink($post),
            'category' => $category,
            'categoryLabel' => $category !== '' ? $content->categoryLabel($category) : '',
            'excerpt' => $excerpt,
            'thumbUrl' => $thumbUrl,
            'thumbAlt' => $thumbAlt !== '' ? $thumbAlt : get_the_title($post),
            'gallerySrcs' => array_column($this->galleryFor($post), 'src'),
            'pdfSrcs' => array_column($this->pdfsFor($post), 'src'),
            'videoUrl' => $this->videoUrlFor($post),
            'logoUrl' => $logoUrl,
            'logoAlt' => $logoAlt !== '' ? $logoAlt : get_the_title($post),
            'siteUrl' => $this->metaWithEnFallback($post, ProjectPostType::META_SITE_URL),
            'role' => $this->metaWithEnFallback($post, ProjectPostType::META_ROLE),
        ];
    }

    /**
     * The project's logo attachment id, falling back to its linked EN translation's (AR projects carry
     * no media of their own — see enTranslationId()).
     *
     * Deliberately NOT routed through metaWithEnFallback(): that helper treats only `''` as empty, but
     * this key is registered as an integer defaulting to `0`, so an unset AR value arrives as the
     * non-empty string `"0"` and the English fallback would never fire. Same shape as the featured-image
     * fallback in toGridCard() above.
     */
    /**
     * The affordance the project's grid tiles should wear, following the linked EN translation.
     *
     * Safe through `metaWithEnFallback()` — unlike the integer keys above, this one is registered with
     * no default, so an unset value really does arrive as `''` and the fallback fires as intended.
     */
    public function iconFor(WP_Post $post): string
    {
        return ProjectPostType::sanitizeIcon(
            $this->metaWithEnFallback($post, ProjectPostType::META_ICON)
        );
    }

    private function logoId(WP_Post $post): int
    {
        $logoId = (int) get_post_meta($post->ID, ProjectPostType::META_LOGO, true);
        if ($logoId !== 0) {
            return $logoId;
        }

        $enId = $this->enTranslationId($post);

        return $enId === 0 ? 0 : (int) get_post_meta($enId, ProjectPostType::META_LOGO, true);
    }

    /**
     * Shape a web-category project for the Website-Making showcase (WebShowcaseRenderer): the
     * browser-chrome card's shot (featured image), lightbox source, filter type, and live URL.
     * Polylang Free does not sync custom meta onto translations, so when this post's own site
     * type/URL are empty the linked EN post's values are used — the live site is language-neutral
     * data, not prose.
     *
     * @return array{title: string, shotUrl: string, fullUrl: string, siteType: string, siteUrl: string}
     */
    public function toWebCard(WP_Post $post): array
    {
        $thumbId = get_post_thumbnail_id($post->ID);
        $shotUrl = $thumbId ? (string) wp_get_attachment_image_url($thumbId, 'large') : '';
        $fullUrl = $thumbId ? (string) wp_get_attachment_image_url($thumbId, 'full') : '';

        $logoId = $this->logoId($post);
        $logoUrl = $logoId ? (string) wp_get_attachment_image_url($logoId, 'large') : '';
        $logoAlt = $logoId ? (string) get_post_meta($logoId, '_wp_attachment_image_alt', true) : '';

        return [
            'title' => get_the_title($post),
            'shotUrl' => $shotUrl,
            'fullUrl' => $fullUrl !== '' ? $fullUrl : $shotUrl,
            'siteType' => ProjectPostType::sanitizeSiteType($this->metaWithEnFallback($post, ProjectPostType::META_SITE_TYPE)),
            'siteUrl' => $this->metaWithEnFallback($post, ProjectPostType::META_SITE_URL),
            // The showcase leads with the brand mark now, same as the home grid; the screenshot is
            // only the fallback for a project that has one and no logo yet.
            'logoUrl' => $logoUrl,
            'logoAlt' => $logoAlt,
        ];
    }

    /**
     * A project's video URL (the handoff's ▶ work-card variant). Language-neutral data, so AR
     * translations read the linked EN post's value — same rationale as the web-showcase meta.
     */
    public function videoUrlFor(WP_Post $post): string
    {
        return $this->metaWithEnFallback($post, ProjectPostType::META_VIDEO_URL);
    }

    /**
     * A post's own meta value, falling back to its linked EN translation's value when empty.
     *
     * Delegates to the shared {@see TranslatedMeta}: this rule was private here until the Arabic
     * clients carousel turned out to need the identical fallback (its cards rendered inert because
     * Polylang does not copy meta to translations). One implementation, two callers.
     */
    private function metaWithEnFallback(WP_Post $post, string $key): string
    {
        return TranslatedMeta::string($post, $key);
    }

    /**
     * The linked English translation's post id, or 0 when there is none (or this already IS the EN
     * post / Polylang is inactive). Translated (e.g. Arabic) projects mirror the English post's
     * media rather than duplicating attachments, so the grid needs the EN id to fall back to.
     */
    private function enTranslationId(WP_Post $post): int
    {
        return TranslatedMeta::englishId($post);
    }

    /**
     * A project's gallery attachment ids, falling back to its linked EN translation's gallery when it
     * has none of its own (the AR seed leaves media on the English post — see enTranslationId()).
     *
     * @return list<int>
     */
    private function galleryIds(WP_Post $project): array
    {
        $ids = $this->normalizeGalleryIds(get_post_meta($project->ID, '_perego_gallery_attachment_ids', true));
        if ($ids !== []) {
            return $ids;
        }

        $enId = $this->enTranslationId($project);

        return $enId === 0 ? [] : $this->normalizeGalleryIds(get_post_meta($enId, '_perego_gallery_attachment_ids', true));
    }

    /**
     * @param mixed $raw
     * @return list<int>
     */
    private function normalizeGalleryIds($raw): array
    {
        return is_array($raw) ? array_values(array_filter(array_map('absint', $raw))) : [];
    }

    /**
     * Resolve the fixed, canonical category slug (`ProjectPostType::CATEGORIES`) for a term that may
     * be a Polylang per-language variant. Polylang gives every language its own term — the English
     * `video` term and a separate Arabic `video-ar` term, linked as translations — so an AR project's
     * own term slug is never one of the four canonical slugs the filter buttons and `categoryLabel()`
     * are keyed on. Without this, AR cards showed the literal term slug ("video-ar") as their category
     * badge, and the filter buttons (which filter by canonical slug) never matched any AR card.
     */
    private function canonicalCategorySlug(WP_Term $term): string
    {
        if (isset(ProjectPostType::CATEGORIES[$term->slug])) {
            return $term->slug;
        }

        if (! function_exists('pll_get_term')) {
            return $term->slug;
        }

        $enTermId = (int) pll_get_term($term->term_id, 'en');
        $enTerm   = $enTermId ? get_term($enTermId, ProjectPostType::TAXONOMY) : null;

        return ($enTerm instanceof WP_Term) ? $enTerm->slug : $term->slug;
    }

    /** @return list<WP_Post> */
    private function projectsForLocale(WP_Post $project, int $limit): array
    {
        return (new WP_Query($this->localeQueryArgs($project) + [
            'post_type' => ProjectPostType::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'ASC',
        ]))->posts;
    }

    /** @return array<string, string> */
    private function localeQueryArgs(WP_Post $project): array
    {
        if (! function_exists('pll_get_post_language')) {
            return [];
        }

        $language = pll_get_post_language($project->ID, 'slug');

        return is_string($language) && $language !== '' ? ['lang' => $language] : [];
    }

    private function buildExcerpt(WP_Post $post, PortfolioContent $content, string $client, string $year): string
    {
        if ($client !== '' || $year !== '') {
            $parts = [];
            if ($client !== '') {
                $parts[] = $content->clientLabel() . ': ' . $client;
            }
            if ($year !== '') {
                $parts[] = $year;
            }

            return implode(' · ', $parts);
        }

        return has_excerpt($post) ? get_the_excerpt($post) : '';
    }
}
