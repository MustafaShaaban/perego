<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\PostBreadcrumbRenderer;
use PeregoSite\Content\GlobalContent;

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;
    }
}

beforeEach(function () {
    Functions\when('esc_attr__')->returnArg(1);
    Functions\when('esc_html')->returnArg(1);
    Functions\when('esc_url')->returnArg(1);
    Functions\when('home_url')->alias(fn (string $path = '/') => 'http://perego.local' . $path);
    Functions\when('get_the_title')->justReturn('Behind the scenes of a brand film');
});

it('renders the EN trail Home / The Perego Journal / <title> with real links', function () {
    $html = (new PostBreadcrumbRenderer(new GlobalContent('en')))->render(new WP_Post());

    expect($html)
        ->toContain('class="page-crumb"')
        ->toContain('href="http://perego.local/">Home</a>')
        ->toContain('href="http://perego.local/journal">The Perego Journal</a>')
        ->toContain('<span aria-current="page">Behind the scenes of a brand film</span>');
});

it('localizes the trail on the AR route (labels come from GlobalContent, not the post)', function () {
    $html = (new PostBreadcrumbRenderer(new GlobalContent('ar')))->render(new WP_Post());

    expect($html)
        ->toContain('>الرئيسية</a>')
        ->toContain('>مدونة بيريجو</a>');
});

it('renders an empty current-page label (and never calls get_the_title) when there is no post', function () {
    // A null queried object must not fatal, and must not reach get_the_title at all.
    Functions\expect('get_the_title')->never();

    $html = (new PostBreadcrumbRenderer(new GlobalContent('en')))->render(null);

    expect($html)
        ->toContain('<span aria-current="page"></span>')
        ->toContain('href="http://perego.local/">Home</a>');
});
