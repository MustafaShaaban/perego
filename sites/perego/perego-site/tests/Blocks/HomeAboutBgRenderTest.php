<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\HomeAboutBgRenderer;

beforeEach(function () {
    Functions\when('esc_url')->returnArg();
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
});

it('renders the background image div locale-independently', function () {
    $html = (new HomeAboutBgRenderer())->render();

    expect($html)->toContain('home-about__bg')
        ->and($html)->toMatch('/<img [^>]*about-hooded-wide\.webp/')
        ->and($html)->toContain('aria-hidden="true"');
});

it('uses no hardcoded hex colors in the rendered markup', function () {
    expect((new HomeAboutBgRenderer())->render())->not->toMatch('/#[0-9a-fA-F]{6}/');
});
