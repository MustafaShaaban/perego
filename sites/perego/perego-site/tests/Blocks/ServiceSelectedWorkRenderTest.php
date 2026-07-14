<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ServiceSelectedWorkRenderer;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
});

function sampleServiceWork(): array
{
    return [
        ['title' => 'Brand Film', 'thumbUrl' => 'https://perego.local/a.jpg', 'thumbAlt' => 'Brand Film', 'gallerySrcs' => []],
        ['title' => 'Product Teaser', 'thumbUrl' => 'https://perego.local/b.jpg', 'thumbAlt' => 'Product Teaser', 'gallerySrcs' => ['https://perego.local/b.jpg', 'https://perego.local/b2.jpg']],
        ['title' => 'No Media Project', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => []],
    ];
}

it('renders no section at all when there is no usable project media', function () {
    $html = (new ServiceSelectedWorkRenderer())->render([
        ['title' => 'No Media', 'thumbUrl' => '', 'thumbAlt' => '', 'gallerySrcs' => []],
    ]);

    expect($html)->toBe('');
});

it('renders a work-masonry of real projects, opening the site-wide media lightbox', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(sampleServiceWork());

    expect($html)->toContain('class="portfolio page-section"')
        ->and($html)->toContain('class="work-masonry"')
        ->and(substr_count($html, 'class="work-card reveal"'))->toBe(2) // the no-media project is skipped
        ->and($html)->toContain('data-image="https://perego.local/a.jpg"')
        ->and($html)->toContain('data-gallery="https://perego.local/b.jpg,https://perego.local/b2.jpg"');
});

it('has no heading of its own, unlike the archive\'s Selected work section', function () {
    $html = (new ServiceSelectedWorkRenderer())->render(sampleServiceWork());

    expect($html)->not->toContain('<h2');
});
