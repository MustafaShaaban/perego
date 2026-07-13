<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Seo\StructuredData;

beforeEach(function () {
    Functions\when('home_url')->alias(fn (string $p = '/') => 'https://perego.local' . ($p === '/' ? '/' : $p));
    Functions\when('get_bloginfo')->alias(fn (string $k = '') => $k === 'name' ? 'Perego Creative Studio' : 'Creative Studio');
    Functions\when('is_singular')->justReturn(false);
    Functions\when('wp_json_encode')->alias(fn ($d, $f = 0) => json_encode($d, $f));
});

it('emits site-wide Organization + WebSite JSON-LD on non-singular routes', function () {
    ob_start();
    (new StructuredData())->render();
    $out = ob_get_clean();

    expect($out)->toContain('application/ld+json')
        ->and($out)->toContain('"@type":"Organization"')
        ->and($out)->toContain('"@type":"WebSite"')
        ->and($out)->toContain('"@type":"SearchAction"')
        ->and($out)->toContain('Perego Creative Studio');

    // Each JSON-LD block must be valid JSON.
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $out, $m);
    expect($m[1])->not->toBeEmpty();
    foreach ($m[1] as $json) {
        expect(json_decode($json, true))->toBeArray();
    }
});

it('does not fabricate ratings, reviews, awards, or prices', function () {
    ob_start();
    (new StructuredData())->render();
    $out = strtolower(ob_get_clean());

    expect($out)->not->toContain('aggregaterating')
        ->and($out)->not->toContain('"review"')
        ->and($out)->not->toContain('"award"')
        ->and($out)->not->toContain('"price"');
});
