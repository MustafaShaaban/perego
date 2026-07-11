<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\LegalTocRenderer;
use PeregoSite\Content\GlobalContent;

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;

        public string $post_name = '';

        public string $post_content = '';
    }
}

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('wp_strip_all_tags')->returnArg();
    Functions\when('get_post_meta')->justReturn('July 1, 2026');
    // Two anchored H2 headings + one unanchored (skipped) + a nested one.
    Functions\when('parse_blocks')->justReturn([
        ['blockName' => 'core/heading', 'attrs' => ['level' => 2, 'anchor' => 's1'], 'innerHTML' => '<h2>1. Acceptance</h2>', 'innerBlocks' => []],
        ['blockName' => 'core/paragraph', 'attrs' => [], 'innerHTML' => '<p>x</p>', 'innerBlocks' => []],
        ['blockName' => 'core/heading', 'attrs' => ['level' => 2], 'innerHTML' => '<h2>No anchor</h2>', 'innerBlocks' => []],
        ['blockName' => 'core/heading', 'attrs' => ['level' => 2, 'anchor' => 's2'], 'innerHTML' => '<h2>2. Services</h2>', 'innerBlocks' => []],
    ]);
});

function renderLegalToc(string $locale = 'en'): string
{
    $post = new WP_Post();
    $post->ID = 7;
    $post->post_content = '<!-- blocks -->';

    return (new LegalTocRenderer(new GlobalContent($locale)))->render($post);
}

it('returns empty output with no page', function () {
    expect((new LegalTocRenderer(new GlobalContent('en')))->render(null))->toBe('');
});

it('builds the TOC from anchored H2 headings only, plus review note and last-updated', function () {
    $html = renderLegalToc();

    expect($html)->toContain('legal-toc__note')
        ->and($html)->toContain('must be reviewed by legal counsel')
        ->and($html)->toContain('Last updated: July 1, 2026')
        ->and($html)->toContain('href="#s1"')
        ->and($html)->toContain('href="#s2"')
        ->and($html)->not->toContain('No anchor')            // unanchored heading skipped
        ->and(substr_count($html, '<li>'))->toBe(2);
});

it('localizes the review note and TOC title into Arabic', function () {
    $html = renderLegalToc('ar');

    expect($html)->toContain('يجب مراجعتها من قبل مستشار قانوني')
        ->and($html)->toContain('في هذه الصفحة');
});
