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

/*
 * spec 021 C12 — the journal templates. `home.html` and `archive.html` carried inline styles that
 * neither `core/query` nor `core/group` can regenerate (`text-align` is not a group support), so both
 * templates rendered as invalid blocks in FSE.
 */

it('restores the journal query loop spacing on both journal templates', function () {
    $attributes = (new TemplateSectionAttributes())->attributesFor([
        'blockName' => 'core/query',
        'attrs' => ['className' => 'journal-archive__inner'],
    ]);

    expect($attributes)->toBe(['style' => 'margin-top:clamp(32px,4vw,52px)']);
});

it('restores the centred archive header', function () {
    $attributes = (new TemplateSectionAttributes())->attributesFor([
        'blockName' => 'core/group',
        'attrs' => ['className' => 'post-hero__inner'],
    ]);

    expect($attributes)->toBe(['style' => 'text-align:center']);
});

it('keys on the block name too, so the same className on another block is untouched', function () {
    $sections = new TemplateSectionAttributes();

    // PortfolioGridRenderer emits its own `.post-hero__inner` inside the rendered block; that markup
    // must never be rewritten by this filter.
    expect($sections->attributesFor([
        'blockName' => 'perego-theme/portfolio-grid',
        'attrs' => ['className' => 'post-hero__inner'],
    ]))->toBe([])
        ->and($sections->attributesFor([
            'blockName' => 'core/group',
            'attrs' => ['className' => 'journal-archive__inner'],
        ]))->toBe([]);
});

it('restores the contact hero anchor', function () {
    $attributes = (new TemplateSectionAttributes())->attributesFor([
        'blockName' => 'core/group',
        'attrs' => ['className' => 'contact-hero'],
    ]);

    expect($attributes)->toBe(['id' => 'contactChoose']);
});

/*
 * spec 021 C14 — the `<main>` landmark shared by archive-perego_service, legal, page and search.
 * `#main` is the skip-link target and `tabindex="-1"` is what lets the skip link move focus there;
 * `core/group` can emit neither, so both are restored together.
 */

it('restores the main landmark id and tabindex on a tagName=main group', function () {
    $attributes = (new TemplateSectionAttributes())->attributesFor([
        'blockName' => 'core/group',
        'attrs' => ['tagName' => 'main'],
    ]);

    expect($attributes)->toBe(['id' => 'main', 'tabindex' => '-1']);
});

it('matches the main landmark on tagName even though those groups carry no className', function () {
    $sections = new TemplateSectionAttributes();

    expect($sections->attributesFor([
        'blockName' => 'core/group',
        'attrs' => ['tagName' => 'main', 'className' => 'anything'],
    ]))->toBe(['id' => 'main', 'tabindex' => '-1'])
        // A section-tag group is not the landmark.
        ->and($sections->attributesFor([
            'blockName' => 'core/group',
            'attrs' => ['tagName' => 'section'],
        ]))->toBe([])
        // …and neither is a main-tagged non-group block.
        ->and($sections->attributesFor([
            'blockName' => 'core/cover',
            'attrs' => ['tagName' => 'main'],
        ]))->toBe([]);
});
