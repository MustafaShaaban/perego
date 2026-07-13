<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use PeregoSite\Content\ServiceContent;
use PeregoSite\PostTypes\ServicePostType;

it('lists the four service slugs in ServicePostType display order', function () {
    expect((new ServiceContent('en'))->slugs())
        ->toBe(array_keys(ServicePostType::SERVICES))
        ->toBe(['video-editing', 'motion-graphics', 'graphic-design', 'website-making']);
});

it('returns English names, subline, intro paragraphs, and four process steps per service', function () {
    $c = new ServiceContent('en');

    expect($c->fullName('video-editing'))->toBe('Video Editing & Post-Production')
        ->and($c->name('motion-graphics'))->toBe('2D Motion Graphics')
        ->and($c->subline('graphic-design'))->toBe('Graphic Design & Brand Identity');

    $intro = $c->intro('video-editing');
    expect($intro)->toHaveCount(2)
        ->and($intro[0])->toContain('professional video editing');

    $steps = $c->processSteps('website-making');
    expect($steps)->toHaveCount(4)
        ->and($steps[0]['label'])->toBe('Discovery & Planning')
        ->and($steps[3]['desc'])->toContain('support');
});

it('localizes every service into Arabic', function () {
    $c = new ServiceContent('ar');

    expect($c->fullName('video-editing'))->toBe('مونتاج الفيديو وما بعد الإنتاج')
        ->and($c->subline('motion-graphics'))->toContain('موشن جرافيك')
        ->and($c->processSteps('graphic-design'))->toHaveCount(4)
        ->and($c->processSteps('graphic-design')[0]['label'])->toBe('فهم العلامة')
        ->and($c->label('whatWeDo'))->toBe('ماذا نفعل');
});

it('exposes the localized services-overview (archive) copy', function () {
    $en = (new ServiceContent('en'))->overview();
    expect($en['h1'])->toBe('Our Services')
        ->and($en['introTitle'])->toBe('One studio, four services')
        ->and($en['processSteps'])->toHaveCount(4)
        ->and($en['ctaButton'])->toBe('Start a Project');

    $ar = (new ServiceContent('ar'))->overview();
    expect($ar['h1'])->toBe('خدماتنا')
        ->and($ar['ctaButton'])->toBe('ابدأ مشروعك');
});

it('falls back to English for an unknown locale', function () {
    expect((new ServiceContent('fr'))->fullName('video-editing'))
        ->toBe('Video Editing & Post-Production');
});

it('returns the raw slug for an unknown service and empty structures for its copy', function () {
    $c = new ServiceContent('en');

    expect($c->name('nope'))->toBe('nope')
        ->and($c->intro('nope'))->toBe(['', ''])
        ->and($c->processSteps('nope'))->toBe([]);
});
