<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\PortfolioContent;

it('returns ordered English filter labels with All first and the four services', function () {
    $labels = (new PortfolioContent('en'))->filterLabels();

    expect(array_keys($labels))->toBe(['all', 'video', 'motion', 'design', 'web'])
        ->and($labels['all'])->toBe('All Projects')
        ->and($labels['web'])->toBe('Website Making');
});

it('localizes filter labels and strings for ar', function () {
    $content = new PortfolioContent('ar');

    expect($content->filterLabels()['all'])->toBe('كل المشاريع')
        ->and($content->categoryLabel('video'))->toBe('مونتاج الفيديو')
        ->and($content->gridStrings()['noResults'])->toContain('لا توجد مشاريع');
});

it('falls back to English for an unknown locale', function () {
    expect((new PortfolioContent('fr'))->heading())->toBe('Our Work');
});

it('returns the raw slug for an unknown category', function () {
    expect((new PortfolioContent('en'))->categoryLabel('unknown'))->toBe('unknown');
});

it('includes heading + intro in the grid strings for a fully bilingual work page', function () {
    $strings = (new PortfolioContent('en'))->gridStrings();

    expect($strings)->toHaveKeys(['groupLabel', 'noResults', 'heading', 'intro'])
        ->and($strings['heading'])->toBe('Our Work');
});
