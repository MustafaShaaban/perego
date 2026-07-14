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

it('includes the archive closing CTA copy in the grid strings, in both languages', function () {
    $en = (new PortfolioContent('en'))->gridStrings();
    $ar = (new PortfolioContent('ar'))->gridStrings();

    expect($en['ctaTitle'])->toBe('Have a project in mind?')
        ->and($en['ctaButton'])->toBe('Start a Project')
        ->and($ar['ctaTitle'])->toBe('هل لديك مشروع في ذهنك؟')
        ->and($ar['ctaButton'])->toBe('ابدأ مشروعك');
});

it('exposes localized single-project (case study) labels in English', function () {
    $labels = (new PortfolioContent('en'))->projectLabels();

    expect($labels['overviewTitle'])->toBe('Overview')
        ->and($labels['challengeTitle'])->toBe('The Challenge')
        ->and($labels['resultTitle'])->toBe('The Result')
        ->and($labels['roleLabel'])->toBe('Our role')
        ->and($labels['ctaButton'])->toBe('Start a Project');
});

it('localizes single-project labels into Arabic', function () {
    $content = new PortfolioContent('ar');

    expect($content->projectLabel('overviewTitle'))->toBe('نظرة عامة')
        ->and($content->projectLabel('relatedTitle'))->toBe('مشاريع ذات صلة')
        ->and($content->projectLabels()['ctaTitle'])->toBe('أعجبك ما رأيت؟');
});

it('returns an empty string for an unknown project label key', function () {
    expect((new PortfolioContent('en'))->projectLabel('nope'))->toBe('');
});
