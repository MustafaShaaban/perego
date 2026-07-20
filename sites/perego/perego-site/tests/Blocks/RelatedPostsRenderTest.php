<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\PostReadingTimeRenderer;
use PeregoSite\Blocks\RelatedPostsRenderer;
use PeregoSite\Content\GlobalContent;

// WP_Post comes from the shared bootstrap.php test double.
if (! class_exists('WP_Term')) {
    class WP_Term
    {
        public int $term_id = 0;

        public string $slug = '';

        public string $name = '';
    }
}

function relatedPost(int $id, string $body = ''): WP_Post
{
    $post = new WP_Post();
    $post->ID = $id;
    $post->post_content = $body !== '' ? $body : str_repeat('word ', 400); // ~2 min read

    return $post;
}

function relatedCategory(string $name): WP_Term
{
    $term = new WP_Term();
    $term->term_id = 9;
    $term->slug = sanitize_key($name);
    $term->name = $name;

    return $term;
}

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('sanitize_key')->alias(fn (string $v) => strtolower($v));
    Functions\when('get_permalink')->alias(fn (WP_Post $post) => 'https://perego.local/journal/post-' . $post->ID);
    Functions\when('get_the_title')->alias(fn (WP_Post $post) => 'Post ' . $post->ID);
    Functions\when('get_the_date')->justReturn('July 2026');
    Functions\when('get_post_thumbnail_id')->justReturn(7);
    Functions\when('wp_get_attachment_image_url')->justReturn('https://perego.local/thumb.png');
    Functions\when('get_the_terms')->justReturn([relatedCategory('Craft')]);
    Functions\when('wp_strip_all_tags')->returnArg();
    Functions\when('strip_shortcodes')->returnArg();
});

function renderRelated(array $posts): string
{
    $content = new GlobalContent('en');

    return (new RelatedPostsRenderer($content, new PostReadingTimeRenderer($content)))->render($posts);
}

it('renders nothing when there are no related posts', function () {
    expect(renderRelated([]))->toBe('');
});

it('renders the handoff related-articles band: hairline section, centered title, post-card grid', function () {
    $html = renderRelated([relatedPost(1), relatedPost(2), relatedPost(3)]);

    expect($html)->toContain('class="page-section post-related"')
        ->and($html)->toContain('border-top:1px solid rgba(255,255,255,0.08)')
        ->and($html)->toContain('Related articles')
        ->and($html)->toContain('class="blog-grid"')
        ->and(substr_count($html, 'class="post-card reveal"'))->toBe(3);
});

it('cards NAVIGATE to the article and never carry a lightbox trigger (handoff §8.2)', function () {
    $html = renderRelated([relatedPost(1)]);

    expect($html)->toContain('href="https://perego.local/journal/post-1"')
        ->and($html)->not->toContain('data-image')
        ->and($html)->not->toContain('data-gallery')
        ->and($html)->not->toContain('data-video');
});

it('renders each card\'s category, title, and "date · N min read" meta', function () {
    $html = renderRelated([relatedPost(1)]);

    expect($html)->toContain('<span class="post-card__cat">Craft</span>')
        ->and($html)->toContain('<h3 class="post-card__title">Post 1</h3>')
        ->and($html)->toContain('<span class="post-card__meta">July 2026 · 2 min read</span>');
});

it('localizes the section title into Arabic', function () {
    $content = new GlobalContent('ar');
    $html = (new RelatedPostsRenderer($content, new PostReadingTimeRenderer($content)))
        ->render([relatedPost(1)]);

    expect($html)->toContain('مقالات ذات صلة');
});
