<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Content\PortfolioContent;
use PeregoSite\Repositories\ProjectRepository;

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;
    }
}

if (! class_exists('WP_Term')) {
    class WP_Term
    {
        public int $term_id = 0;

        public string $slug = '';
    }
}

function perego_project_post(int $id): WP_Post
{
    $post = new WP_Post();
    $post->ID = $id;

    return $post;
}

function perego_project_term(int $termId, string $slug): WP_Term
{
    $term = new WP_Term();
    $term->term_id = $termId;
    $term->slug = $slug;

    return $term;
}

beforeEach(function () {
    Functions\when('get_the_title')->justReturn('Brand Film — Launch Campaign');
    Functions\when('get_permalink')->justReturn('https://perego.local/work/brand-film');
    Functions\when('get_post_meta')->justReturn('');
    Functions\when('has_excerpt')->justReturn(false);
    Functions\when('get_post_thumbnail_id')->justReturn(0);
});

it('uses the term slug directly when it is already one of the four canonical categories', function () {
    Functions\when('get_the_terms')->justReturn([perego_project_term(30, 'video')]);

    $card = (new ProjectRepository())->toGridCard(perego_project_post(1), new PortfolioContent('en'));

    expect($card['category'])->toBe('video')
        ->and($card['categoryLabel'])->toBe('Video Editing');
});

it('falls back to the raw slug when no English translation is found for a non-canonical term', function () {
    // Covers both "Polylang active but this term has no EN translation" and, functionally
    // equivalently, "Polylang inactive" (pll_get_term absent entirely) - the resolver degrades the
    // same way either way: keep the term's own slug rather than guess.
    Functions\when('get_the_terms')->justReturn([perego_project_term(72, 'video-ar')]);
    Functions\when('pll_get_term')->justReturn(0);

    $card = (new ProjectRepository())->toGridCard(perego_project_post(1), new PortfolioContent('en'));

    expect($card['category'])->toBe('video-ar');
});

it('resolves a Polylang per-language term to its canonical slug', function () {
    // Regression test: an Arabic project is tagged with its own "video-ar" term (Polylang gives every
    // language its own term, linked as a translation of the English "video" one) - the grid used to
    // show the literal slug "video-ar" as the badge, and the filter buttons (keyed on "video") never
    // matched the card at all.
    Functions\when('get_the_terms')->justReturn([perego_project_term(72, 'video-ar')]);
    Functions\when('pll_get_term')->alias(fn (int $termId, string $lang) => $termId === 72 && $lang === 'en' ? 30 : 0);
    Functions\when('get_term')->alias(fn (int $termId) => $termId === 30 ? perego_project_term(30, 'video') : null);

    $card = (new ProjectRepository())->toGridCard(perego_project_post(1), new PortfolioContent('ar'));

    expect($card['category'])->toBe('video')
        ->and($card['categoryLabel'])->toBe('مونتاج الفيديو');
});

it('shapes a web project for the showcase card, reading its own site meta', function () {
    Functions\when('get_post_thumbnail_id')->justReturn(9);
    Functions\when('wp_get_attachment_image_url')->alias(
        fn (int $id, string $size) => $size === 'full' ? 'https://perego.local/p1.png' : 'https://perego.local/p1-large.png'
    );
    Functions\when('get_post_meta')->alias(function (int $postId, string $key) {
        return ['_perego_site_type' => 'ecommerce', '_perego_site_url' => 'https://aurora-retail.com'][$key] ?? '';
    });

    $card = (new ProjectRepository())->toWebCard(perego_project_post(1));

    expect($card['shotUrl'])->toBe('https://perego.local/p1-large.png')
        ->and($card['fullUrl'])->toBe('https://perego.local/p1.png')
        ->and($card['siteType'])->toBe('ecommerce')
        ->and($card['siteUrl'])->toBe('https://aurora-retail.com');
});

it('falls back to the linked EN translation\'s site meta for an AR project (Polylang does not sync meta)', function () {
    Functions\when('get_post_thumbnail_id')->justReturn(9);
    Functions\when('wp_get_attachment_image_url')->justReturn('https://perego.local/p1.png');
    Functions\when('get_post_meta')->alias(function (int $postId, string $key) {
        if ($postId === 7) { // the EN counterpart carries the data
            return ['_perego_site_type' => 'corporate', '_perego_site_url' => 'https://meridiangroup.co'][$key] ?? '';
        }

        return '';
    });
    Functions\when('pll_get_post')->alias(fn (int $postId, string $lang) => $postId === 2 && $lang === 'en' ? 7 : 0);

    $card = (new ProjectRepository())->toWebCard(perego_project_post(2));

    expect($card['siteType'])->toBe('corporate')
        ->and($card['siteUrl'])->toBe('https://meridiangroup.co');
});

it('whitelists a corrupted site-type meta value down to "" in the web card', function () {
    Functions\when('get_post_thumbnail_id')->justReturn(9);
    Functions\when('wp_get_attachment_image_url')->justReturn('https://perego.local/p1.png');
    Functions\when('get_post_meta')->alias(
        fn (int $postId, string $key) => $key === '_perego_site_type' ? 'not-a-real-type' : ''
    );
    Functions\when('pll_get_post')->justReturn(0);

    $card = (new ProjectRepository())->toWebCard(perego_project_post(1));

    expect($card['siteType'])->toBe('');
});

it('falls back to the linked EN translation\'s featured image for an AR grid card (AR seed has none of its own)', function () {
    Functions\when('get_the_terms')->justReturn([perego_project_term(30, 'video')]);
    // Only the EN counterpart (post 7) has a thumbnail; the AR post (2) has none.
    Functions\when('get_post_thumbnail_id')->alias(fn (int $postId) => $postId === 7 ? 42 : 0);
    Functions\when('pll_get_post')->alias(fn (int $postId, string $lang) => $postId === 2 && $lang === 'en' ? 7 : 0);
    Functions\when('wp_get_attachment_image_url')->alias(fn (int $id, string $size) => $id === 42 ? 'https://perego.local/en-thumb-large.png' : '');

    $card = (new ProjectRepository())->toGridCard(perego_project_post(2), new PortfolioContent('ar'));

    expect($card['thumbUrl'])->toBe('https://perego.local/en-thumb-large.png');
});

it('does not fall back when the AR grid card already has its own featured image', function () {
    Functions\when('get_the_terms')->justReturn([perego_project_term(30, 'video')]);
    Functions\when('get_post_thumbnail_id')->alias(fn (int $postId) => $postId === 2 ? 99 : 42);
    Functions\when('pll_get_post')->alias(fn (int $postId, string $lang) => $postId === 2 && $lang === 'en' ? 7 : 0);
    Functions\when('wp_get_attachment_image_url')->alias(fn (int $id, string $size) => 'https://perego.local/thumb-' . $id . '.png');

    $card = (new ProjectRepository())->toGridCard(perego_project_post(2), new PortfolioContent('ar'));

    expect($card['thumbUrl'])->toBe('https://perego.local/thumb-99.png'); // its own, not the EN post's
});

it('falls back to the linked EN translation\'s gallery for an AR project with none of its own', function () {
    Functions\when('absint')->alias(fn ($v) => abs((int) $v));
    Functions\when('get_post_meta')->alias(fn (int $postId, string $key) => ($postId === 7 && $key === '_perego_gallery_attachment_ids') ? [11, 12] : '');
    Functions\when('pll_get_post')->alias(fn (int $postId, string $lang) => $postId === 2 && $lang === 'en' ? 7 : 0);
    Functions\when('wp_get_attachment_image_url')->alias(fn (int $id, string $size) => 'https://perego.local/g' . $id . '-' . $size . '.png');

    $gallery = (new ProjectRepository())->galleryFor(perego_project_post(2));

    expect($gallery)->toHaveCount(2)
        ->and($gallery[0]['src'])->toBe('https://perego.local/g11-full.png');
});
