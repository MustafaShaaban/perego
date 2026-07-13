<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Seo\PeregoAgentReadiness;

beforeEach(function () {
    Functions\when('esc_url')->returnArg();
});

it('recognises the /llms.txt request path, with or without a trailing slash or query', function () {
    $readiness = new PeregoAgentReadiness();

    expect($readiness->isLlmsRequest('/llms.txt'))->toBeTrue()
        ->and($readiness->isLlmsRequest('/llms.txt/'))->toBeTrue()
        ->and($readiness->isLlmsRequest('/llms.txt?foo=bar'))->toBeTrue()
        ->and($readiness->isLlmsRequest('/'))->toBeFalse()
        ->and($readiness->isLlmsRequest('/llms.txt.php'))->toBeFalse()
        ->and($readiness->isLlmsRequest('/services'))->toBeFalse();
});

it('builds an llms.txt body with a title, a description blockquote, and section links', function () {
    $body = (new PeregoAgentReadiness())->llmsBody([
        'name' => 'Perego',
        'description' => 'Creative Studio',
        'home' => 'https://perego.local/',
        'sections' => [
            ['label' => 'Services', 'url' => 'https://perego.local/services/'],
            ['label' => 'Work', 'url' => 'https://perego.local/work/'],
        ],
    ]);

    expect($body)->toStartWith('# Perego')
        ->and($body)->toContain('> Creative Studio')
        ->and($body)->toContain('- [Services](https://perego.local/services/)')
        ->and($body)->toContain('- [Work](https://perego.local/work/)');
});

it('renders an x-default alternate link to the default-language home', function () {
    $html = (new PeregoAgentReadiness())->alternateXDefaultTag('https://perego.local/');

    expect($html)->toContain('rel="alternate"')
        ->and($html)->toContain('hreflang="x-default"')
        ->and($html)->toContain('href="https://perego.local/"');
});
