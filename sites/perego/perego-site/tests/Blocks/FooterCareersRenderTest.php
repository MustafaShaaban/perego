<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\FooterCareersRenderer;

beforeEach(function () {
    Functions\when('wp_kses_post')->returnArg();
    Functions\when('do_blocks')->returnArg();
});

/** @param array<string,mixed> $attributes */
function renderFooterCareers(string $locale, array $attributes): string
{
    return (new FooterCareersRenderer($locale))->render($attributes);
}

$attrs = [
    'headingEn' => 'Join us',
    'headingAr' => 'انضم إلينا',
    'blurbEn' => 'Grow with us.',
    'blurbAr' => 'انمُ معنا.',
];

it('renders only the English variant in the en locale', function () use ($attrs) {
    $html = renderFooterCareers('en', $attrs);

    expect($html)->toContain('<h2 class="wp-block-heading footer-heading">Join us</h2>')
        ->and($html)->toContain('<p class="footer-blurb">Grow with us.</p>')
        ->and($html)->not->toContain('انضم إلينا')
        ->and($html)->not->toContain('انمُ معنا.');
});

it('renders only the Arabic variant in the ar locale', function () use ($attrs) {
    $html = renderFooterCareers('ar', $attrs);

    expect($html)->toContain('<h2 class="wp-block-heading footer-heading">انضم إلينا</h2>')
        ->and($html)->toContain('<p class="footer-blurb">انمُ معنا.</p>')
        ->and($html)->not->toContain('Join us')
        ->and($html)->not->toContain('Grow with us.');
});

it('reconstructs the exact core-block markup the footer-careers record held', function () use ($attrs) {
    // do_blocks is stubbed to return its argument, so we assert on the pre-render markup contract.
    $html = renderFooterCareers('en', $attrs);

    expect($html)->toContain('<!-- wp:heading {"level":2,"className":"footer-heading"} -->')
        ->and($html)->toContain('<!-- wp:paragraph {"className":"footer-blurb"} -->');
});

it('renders nothing when both heading and blurb are empty', function () {
    expect(renderFooterCareers('en', ['headingEn' => '', 'blurbEn' => '']))->toBe('');
});
