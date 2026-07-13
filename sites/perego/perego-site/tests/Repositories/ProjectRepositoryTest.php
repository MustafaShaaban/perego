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
