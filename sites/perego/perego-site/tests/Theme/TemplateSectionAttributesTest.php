<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Theme\TemplateSectionAttributes;

/**
 * Covers the pure lookup (spec 021 T035). `apply()` itself is three calls into core's
 * WP_HTML_Tag_Processor and is exercised against the real front end (the rendered `.home-about`
 * opening tag is diffed before/after the template change), not stubbed here.
 */
$group = static fn (?string $className): array => [
    'blockName' => 'core/group',
    'attrs' => $className === null ? [] : ['className' => $className],
];

it('restores the homepage About anchor and landmark label', function () use ($group) {
    $attributes = (new TemplateSectionAttributes())->attributesFor($group('home-about'));

    expect($attributes)->toBe([
        'id' => 'about',
        'aria-labelledby' => 'home-about-title',
    ]);
});

it('leaves every other group alone', function () use ($group) {
    $sections = new TemplateSectionAttributes();

    expect($sections->attributesFor($group('container home-about__inner')))->toBe([])
        ->and($sections->attributesFor($group('page-section')))->toBe([])
        ->and($sections->attributesFor($group(null)))->toBe([]);
});

it('ignores blocks that are not core groups', function () {
    $sections = new TemplateSectionAttributes();

    expect($sections->attributesFor([
        'blockName' => 'perego-theme/home-about-bg',
        'attrs' => ['className' => 'home-about'],
    ]))->toBe([])
        // A freeform/HTML chunk in a template parses with a null blockName.
        ->and($sections->attributesFor(['blockName' => null, 'attrs' => []]))->toBe([])
        ->and($sections->attributesFor([]))->toBe([]);
});

it('returns the html untouched when the block owns no restored attributes', function () {
    $html = '<section class="wp-block-group page-section"></section>';

    expect((new TemplateSectionAttributes())->apply($html, [
        'blockName' => 'core/group',
        'attrs' => ['className' => 'page-section'],
    ]))->toBe($html);
});
