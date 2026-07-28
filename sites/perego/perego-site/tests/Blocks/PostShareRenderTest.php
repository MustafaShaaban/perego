<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\PostShareRenderer;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('get_permalink')->justReturn('https://perego.local/journal/a-post/');
    Functions\when('get_the_title')->justReturn('A post & its title');
});

function renderShare(): string
{
    return (new PostShareRenderer())->render(Mockery::mock(WP_Post::class));
}

it('renders one link per network plus a copy button', function () {
    $html = renderShare();

    expect(substr_count($html, 'class="post-share__link"'))->toBe(4)
        ->and($html)->toContain('post-share__copy')
        ->and($html)->toContain('Share on X')
        ->and($html)->toContain('Share on LinkedIn')
        ->and($html)->toContain('Share on Facebook')
        ->and($html)->toContain('Share on WhatsApp');
});

it('url-encodes the permalink and title into every share target', function () {
    $html = renderShare();

    $url = rawurlencode('https://perego.local/journal/a-post/');

    expect($html)->toContain('https://twitter.com/intent/tweet?url=' . $url)
        ->and($html)->toContain('https://www.linkedin.com/sharing/share-offsite/?url=' . $url)
        ->and($html)->toContain('https://www.facebook.com/sharer/sharer.php?u=' . $url)
        // The ampersand in the title must survive encoding, not break the query string.
        ->and($html)->toContain(rawurlencode('A post & its title'));
});

it('sends WhatsApp a single pre-composed text parameter, since wa.me takes no separate url', function () {
    expect(renderShare())->toContain(
        'https://wa.me/?text=' . rawurlencode('A post & its title https://perego.local/journal/a-post/')
    );
});

it('opens external shares safely in a new tab', function () {
    $html = renderShare();

    expect(substr_count($html, 'rel="noopener noreferrer"'))->toBe(4)
        ->and(substr_count($html, 'target="_blank"'))->toBe(4);
});

it('gives the copy button the raw url for its script, and an aria-live confirmation', function () {
    $html = renderShare();

    expect($html)->toContain('data-perego-copy="https://perego.local/journal/a-post/"')
        ->and($html)->toContain('aria-live="polite"')
        ->and($html)->toContain('hidden');
});

it('renders nothing without a post, rather than an empty row', function () {
    expect((new PostShareRenderer())->render(null))->toBe('');
});

it('renders nothing when the post has no permalink', function () {
    Functions\when('get_permalink')->justReturn('');

    expect(renderShare())->toBe('');
});

it('uses no hardcoded hex colors in the rendered markup', function () {
    expect(renderShare())->not->toMatch('/#[0-9a-fA-F]{6}/');
});
