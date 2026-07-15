<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\PreloaderRenderer;

beforeEach(function () {
    Functions\when('esc_html__')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://example.test/wp-content/themes/perego-theme');
    Functions\when('wp_json_encode')->alias('json_encode');
});

it('renders the locked handoff preloader structure with the Interactivity API wiring', function () {
    $html = (new PreloaderRenderer())->render();

    expect($html)->toContain('class="preloader"')
        ->and($html)->toContain('data-wp-interactive="perego/preloader"')
        ->and($html)->toContain('data-wp-init="callbacks.init"')
        ->and($html)->toContain('data-wp-class--is-hidden="context.isHidden"')
        ->and($html)->toContain('preloader__stage')
        ->and($html)->toContain('preloader__ring preloader__ring--1')
        ->and($html)->toContain('preloader__ring preloader__ring--2')
        ->and($html)->toContain('preloader__ring preloader__ring--3')
        ->and($html)->toContain('preloader__glow')
        ->and($html)->toContain('preloader__logo')
        ->and($html)->toContain('/assets/images/logo-full.png')
        ->and($html)->toContain('preloader__bar')
        ->and($html)->toContain('preloader__word');
});

it('marks the region so screen readers do not announce it as content', function () {
    $html = (new PreloaderRenderer())->render();

    expect($html)->toMatch('/role="presentation"|aria-hidden="true"/');
});
