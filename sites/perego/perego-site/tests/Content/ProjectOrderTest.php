<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Content\ProjectOrder;

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;
    }
}

function perego_order_post(int $id): WP_Post
{
    $post = new WP_Post();
    $post->ID = $id;

    return $post;
}

/** @param list<WP_Post> $posts */
function perego_order_ids(array $posts): array
{
    return array_map(static fn (WP_Post $post): int => $post->ID, $posts);
}

it('leaves the posts alone when no manual order is stored', function () {
    $posts = [perego_order_post(1), perego_order_post(2), perego_order_post(3)];

    expect(perego_order_ids(ProjectOrder::apply($posts, [])))->toBe([1, 2, 3]);
});

it('reorders the named posts into the order given', function () {
    $posts = [perego_order_post(1), perego_order_post(2), perego_order_post(3)];

    expect(perego_order_ids(ProjectOrder::apply($posts, [3, 1, 2])))->toBe([3, 1, 2]);
});

/*
 * The rule that keeps a deliberate arrangement stable: publishing a project must not push itself to
 * the top of a grid someone curated. It waits at the end until an editor places it.
 */
it('appends anything the order does not name, keeping the query order', function () {
    $posts = [perego_order_post(1), perego_order_post(2), perego_order_post(3), perego_order_post(4)];

    expect(perego_order_ids(ProjectOrder::apply($posts, [3, 1])))->toBe([3, 1, 2, 4]);
});

it('skips an id that no longer resolves rather than leaving a hole', function () {
    $posts = [perego_order_post(1), perego_order_post(2)];

    // 99 was unpublished, deleted, or filtered out by featuredOnly since the order was saved.
    expect(perego_order_ids(ProjectOrder::apply($posts, [99, 2, 1])))->toBe([2, 1]);
});

it('ignores a duplicated id rather than rendering the same project twice', function () {
    $posts = [perego_order_post(1), perego_order_post(2)];

    expect(perego_order_ids(ProjectOrder::apply($posts, [2, 2, 1])))->toBe([2, 1]);
});

/*
 * The two-index rule. A grid's order is authored against the English posts because both grid
 * instances live in language-neutral FSE templates, but the Arabic page renders the Arabic posts —
 * so the saved English ids have to resolve to their translations or the Arabic grid would silently
 * ignore the order entirely.
 */
it('resolves an English-authored order against the Arabic posts it translates', function () {
    Functions\when('pll_get_post')->alias(
        fn (int $postId, string $lang) => $lang === 'en' ? ['101' => 1, '102' => 2, '103' => 3][(string) $postId] ?? 0 : 0
    );

    $posts = [perego_order_post(101), perego_order_post(102), perego_order_post(103)];

    expect(perego_order_ids(ProjectOrder::apply($posts, [3, 1, 2])))->toBe([103, 101, 102]);
});

it('still resolves an order authored in the post\'s own language', function () {
    Functions\when('pll_get_post')->alias(
        fn (int $postId, string $lang) => $lang === 'en' ? ['101' => 1, '102' => 2][(string) $postId] ?? 0 : 0
    );

    $posts = [perego_order_post(101), perego_order_post(102)];

    // A Service post owns its own block, so an Arabic service saves Arabic ids.
    expect(perego_order_ids(ProjectOrder::apply($posts, [102, 101])))->toBe([102, 101]);
});

it('degrades to raw ids when Polylang is inactive', function () {
    // No pll_get_post defined at all — the constitution's "no optional plugin is a hard dependency".
    $posts = [perego_order_post(1), perego_order_post(2)];

    expect(perego_order_ids(ProjectOrder::apply($posts, [2, 1])))->toBe([2, 1]);
});

it('counts how many posts the order has not placed yet', function () {
    $posts = [perego_order_post(1), perego_order_post(2), perego_order_post(3)];

    expect(ProjectOrder::unplacedCount($posts, [3, 1]))->toBe(1)
        ->and(ProjectOrder::unplacedCount($posts, [1, 2, 3]))->toBe(0)
        // No manual order means nothing is out of place, rather than everything being.
        ->and(ProjectOrder::unplacedCount($posts, []))->toBe(0);
});

it('does not count an Arabic post as unplaced when its English id is named', function () {
    Functions\when('pll_get_post')->alias(
        fn (int $postId, string $lang) => $lang === 'en' ? ['101' => 1, '102' => 2][(string) $postId] ?? 0 : 0
    );

    expect(ProjectOrder::unplacedCount([perego_order_post(101), perego_order_post(102)], [1, 2]))->toBe(0);
});
