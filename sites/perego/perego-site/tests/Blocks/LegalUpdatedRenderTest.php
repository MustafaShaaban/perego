<?php

/** @package PeregoSite */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\LegalUpdatedRenderer;
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
    Functions\when('wp_date')->justReturn('July 1, 2026');
    Functions\when('get_post_timestamp')->justReturn(1719792000);
});

function makeLegalPage(): WP_Post
{
    $post = new WP_Post();
    $post->ID = 38;

    return $post;
}

it('returns empty output with no page', function () {
    expect((new LegalUpdatedRenderer(new GlobalContent('en')))->render(null))->toBe('');
});

it('renders the handoff "Last updated" line from the curated meta', function () {
    Functions\when('get_post_meta')->justReturn('2026-07-01');

    $html = (new LegalUpdatedRenderer(new GlobalContent('en')))->render(makeLegalPage());

    expect($html)->toBe('<p class="legal-updated">Last updated: July 1, 2026</p>');
});

it('falls back to the page modified date when the meta is empty', function () {
    Functions\when('get_post_meta')->justReturn('');

    $html = (new LegalUpdatedRenderer(new GlobalContent('en')))->render(makeLegalPage());

    expect($html)->toContain('Last updated: July 1, 2026');
});

it('returns empty when there is neither a curated date nor a modified date', function () {
    Functions\when('get_post_meta')->justReturn('');
    Functions\when('get_post_timestamp')->justReturn(0);

    expect((new LegalUpdatedRenderer(new GlobalContent('en')))->render(makeLegalPage()))->toBe('');
});

it('uses the localized "Last updated" label', function () {
    Functions\when('get_post_meta')->justReturn('2026-07-01');

    $html = (new LegalUpdatedRenderer(new GlobalContent('ar')))->render(makeLegalPage());

    expect($html)->toContain((new GlobalContent('ar'))->legal()['lastUpdated']);
});
