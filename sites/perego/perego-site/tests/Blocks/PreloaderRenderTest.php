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
    Functions\when('wp_json_encode')->alias('json_encode');
});

it('renders the branded preloader markup with the Interactivity API wiring', function () {
    $html = (new PreloaderRenderer())->render();

    expect($html)->toContain('perego-preloader')
        ->and($html)->toContain('data-wp-interactive="perego/preloader"')
        ->and($html)->toContain('data-wp-init="callbacks.init"')
        ->and($html)->toContain('data-wp-class--is-hidden="context.isHidden"')
        ->and($html)->toContain('aria-hidden');
});

it('marks the region so screen readers do not announce it as content', function () {
    $html = (new PreloaderRenderer())->render();

    expect($html)->toMatch('/role="presentation"|aria-hidden="true"/');
});
