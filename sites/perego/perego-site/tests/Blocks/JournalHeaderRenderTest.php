<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\JournalHeaderRenderer;
use PeregoSite\Content\GlobalContent;

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
});

it('renders the journal H1 and lead in English', function () {
    $html = (new JournalHeaderRenderer(new GlobalContent('en')))->render();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('The Perego Journal')
        ->and($html)->toContain('from the studio floor');
});

it('renders a Home breadcrumb', function () {
    $html = (new JournalHeaderRenderer(new GlobalContent('en')))->render();

    expect($html)->toContain('class="page-crumb"')
        ->and($html)->toMatch('/<a href="[^"]*\/">Home<\/a>/')
        ->and($html)->toContain('aria-current="page">The Perego Journal');
});

it('localizes the journal header into Arabic', function () {
    $html = (new JournalHeaderRenderer(new GlobalContent('ar')))->render();

    expect($html)->toContain('مدونة بيريجو');
});

/*
 * spec 021 C12 — the journal-header block's editable title/lead. Overrides are already locale-resolved
 * by LocalizedAttributes; the Content class owns the seed-vs-override rule.
 */

it('renders the seed heading and lead when the block passes no overrides', function () {
    $seed = (new JournalHeaderRenderer(new GlobalContent('en')))->render();

    expect($seed)->toContain('The Perego Journal')
        ->and($seed)->toBe((new JournalHeaderRenderer(new GlobalContent('en'), []))->render());
});

it('applies a non-empty title/lead override', function () {
    $html = (new JournalHeaderRenderer(new GlobalContent('en'), [
        'title' => 'Studio Notes',
        'lead' => 'Short dispatches.',
    ]))->render();

    expect($html)->toContain('Studio Notes')
        ->and($html)->toContain('Short dispatches.')
        ->and($html)->not->toContain('The Perego Journal');
});

it('treats an empty or whitespace override as "use the seed", never as a blank heading', function () {
    $html = (new JournalHeaderRenderer(new GlobalContent('en'), ['title' => '', 'lead' => '   ']))->render();

    expect($html)->toContain('The Perego Journal')
        ->and($html)->toContain('Notes on video, motion, design and the web');
});
