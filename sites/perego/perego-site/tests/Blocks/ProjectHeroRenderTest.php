<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ProjectHeroRenderer;
use PeregoSite\Content\PortfolioContent;

if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;

        public string $post_name = '';
    }
}

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('home_url')->alias(fn (string $path = '') => 'https://perego.local' . $path);
    Functions\when('get_the_title')->justReturn('Brand Film — Launch Campaign');
    Functions\when('get_the_terms')->justReturn([(object) ['slug' => 'video', 'name' => 'Video Editing']]);
    Functions\when('get_post_meta')->alias(function (int $id, string $key) {
        return [
            '_perego_client' => 'Sample Client',
            '_perego_year' => '2026',
            '_perego_role' => 'Editing & Post-Production',
            '_perego_deliverables' => 'Hero film, 3 social cutdowns',
        ][$key] ?? '';
    });
    Functions\when('has_post_thumbnail')->justReturn(false);
    Functions\when('get_the_post_thumbnail')->justReturn('');
});

function renderProjectHero(string $locale = 'en'): string
{
    $post = new WP_Post();
    $post->ID = 42;
    $post->post_name = 'brand-film-launch-campaign';

    return (new ProjectHeroRenderer(new PortfolioContent($locale)))->render($post);
}

it('returns empty output when there is no queried project', function () {
    expect((new ProjectHeroRenderer(new PortfolioContent('en')))->render(null))->toBe('');
});

it('renders one H1 (the project title), a breadcrumb, and the service-category eyebrow', function () {
    $html = renderProjectHero();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Brand Film — Launch Campaign')
        ->and($html)->toContain('project-hero__crumb')
        ->and($html)->toContain('Video Editing')
        ->and($html)->toMatch('/href="[^"]*\/work"/');
});

it('renders the four meta rows from post meta with localized labels', function () {
    $html = renderProjectHero();

    expect(substr_count($html, 'project-hero__meta-item'))->toBe(4)
        ->and($html)->toContain('Client')
        ->and($html)->toContain('Sample Client')
        ->and($html)->toContain('Our role')
        ->and($html)->toContain('Hero film, 3 social cutdowns');
});

it('localizes the breadcrumb and meta labels into Arabic', function () {
    $html = renderProjectHero('ar');

    expect($html)->toContain('الرئيسية')
        ->and($html)->toContain('أعمالنا')
        ->and($html)->toContain('العميل')
        ->and($html)->toContain('دورنا');
});

it('renders the featured image when the project has a thumbnail', function () {
    Functions\when('has_post_thumbnail')->justReturn(true);
    Functions\when('get_the_post_thumbnail')->justReturn('<img src="https://perego.local/x.png" alt="" />');

    expect(renderProjectHero())->toContain('project-hero__featured')
        ->and(renderProjectHero())->toContain('https://perego.local/x.png');
});

it('omits the featured-image wrapper when the project has no thumbnail', function () {
    expect(renderProjectHero())->not->toContain('project-hero__featured');
});
