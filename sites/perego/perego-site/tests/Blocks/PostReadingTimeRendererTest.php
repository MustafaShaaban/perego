<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\PostReadingTimeRenderer;
use PeregoSite\Content\GlobalContent;

// WP_Post double comes from tests/bootstrap.php (shared, #[AllowDynamicProperties] + post_content).

beforeEach(function () {
    Functions\when('esc_html')->returnArg(1);
    Functions\when('strip_shortcodes')->returnArg(1);
    Functions\when('wp_strip_all_tags')->alias(fn (string $s) => trim(preg_replace('/<[^>]*>/', '', $s)));
});

function makePost(string $content): WP_Post
{
    $post = new WP_Post();
    $post->post_content = $content;

    return $post;
}

it('estimates minutes at 200 wpm, floored to 1, and localizes the EN label', function () {
    // 250 words → ceil(250/200) = 2 minutes.
    $html = (new PostReadingTimeRenderer(new GlobalContent('en')))
        ->render(makePost(str_repeat('word ', 250)));

    expect($html)
        ->toContain('class="post-single__readtime"')
        ->toContain('2 min read');
});

it('floors a very short post to a 1-minute minimum', function () {
    $html = (new PostReadingTimeRenderer(new GlobalContent('en')))
        ->render(makePost('Just three words'));

    expect($html)->toContain('1 min read');
});

it('counts Arabic (whitespace-delimited) words and localizes the AR label', function () {
    // str_word_count() would return 0 for Arabic; the unicode split must count these 5 words → 1 min.
    $html = (new PostReadingTimeRenderer(new GlobalContent('ar')))
        ->render(makePost('واحد اثنان ثلاثة أربعة خمسة'));

    expect($html)
        ->toContain('1 دقيقة للقراءة')
        ->toContain('class="post-single__readtime"');
});

it('renders nothing for a null queried object', function () {
    $html = (new PostReadingTimeRenderer(new GlobalContent('en')))->render(null);

    expect($html)->toBe('');
});

it('renders nothing when the post body is empty', function () {
    $html = (new PostReadingTimeRenderer(new GlobalContent('en')))->render(makePost('   '));

    expect($html)->toBe('');
});
