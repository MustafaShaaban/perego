<?php

/** @package PeregoSite */

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

it('builds the handoff TOC from anchored H2 headings only', function () {
    $html = renderLegalToc();

    expect($html)->toContain('<aside class="legal-toc"')
        ->and($html)->toContain('<h2>On this page</h2>')
        ->and($html)->toContain('href="#s1"')
        ->and($html)->toContain('href="#s2"')
        ->and($html)->not->toContain('No anchor')
        ->and(substr_count($html, '<li>'))->toBe(2);
});

it('uses the localized TOC title', function () {
    $html = renderLegalToc('ar');

    expect($html)->toContain((new GlobalContent('ar'))->legal()['tocTitle']);
});
