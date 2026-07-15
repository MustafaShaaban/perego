<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\JoinFormRenderer;
use PeregoSite\Content\GlobalContent;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('rest_url')->alias(fn (string $p) => 'https://perego.local/wp-json/' . $p);
    Functions\when('wp_create_nonce')->justReturn('nonce123');
    Functions\when('wp_json_encode')->alias(fn ($v) => json_encode($v));
});

function renderJoin(string $locale = 'en'): string
{
    return (new JoinFormRenderer(new GlobalContent($locale)))->render();
}

it('renders the handoff join fields, a required CV upload, and the honeypot', function () {
    $html = renderJoin();

    expect($html)->toContain('name="name"')
        ->and($html)->toContain('name="email"')
        ->and($html)->toContain('name="portfolio"')
        ->and($html)->toContain('placeholder="Put your name here"')
        ->and($html)->toContain('placeholder="Put your Portfolio/website link"')
        ->and($html)->toContain('type="file" id="jf-cv" name="cv" accept=".pdf,.doc,.docx" required')
        ->and($html)->toContain('name="perego_hp"')
        ->and(substr_count($html, '<form'))->toBe(1);
});

it('wires the secure endpoint, a nonce, the size cap, and the localized state messages', function () {
    $html = renderJoin();

    expect($html)->toContain('data-endpoint="https://perego.local/wp-json/perego/v1/careers/apply"')
        ->and($html)->toContain('data-nonce="nonce123"')
        ->and($html)->toContain('data-max-bytes="10485760"')
        ->and($html)->toContain('data-messages=')
        ->and($html)->toContain('wrong_type')
        ->and($html)->toContain('too_large');
});

it('exposes an aria-live status region for the upload lifecycle', function () {
    $html = renderJoin();

    expect($html)->toContain('role="status"')
        ->and($html)->toContain('aria-live="polite"')
        ->and($html)->toContain('id="join-form-status"');
});

it('localizes the labels into Arabic', function () {
    $html = renderJoin('ar');

    expect($html)->toContain('الاسم الكامل')
        ->and($html)->toContain('قدّم الآن')
        ->and($html)->toContain('السيرة الذاتية');
});
