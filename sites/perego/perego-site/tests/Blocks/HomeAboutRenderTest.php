<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\HomeAboutRenderer;

/**
 * Stand-in for core's WP_Block. The renderer's whole job is to hand `core/post-content` the right
 * context and return what core makes of it, so the double records what it was constructed with and
 * renders that back — letting the tests assert the delegation contract itself (block name, layout
 * attributes, and which page's content is asked for).
 */
if (! class_exists('WP_Block')) {
    class WP_Block
    {
        /** @var array<string, mixed> */
        public array $parsed_block;

        /** @var array<string, mixed> */
        public array $context;

        /**
         * @param array<string, mixed> $parsedBlock
         * @param array<string, mixed> $context
         */
        public function __construct(array $parsedBlock, array $context = [])
        {
            $this->parsed_block = $parsedBlock;
            $this->context = $context;
        }

        public function render(): string
        {
            return sprintf(
                '[rendered %s post=%s type=%s layout=%s]',
                (string) ($this->parsed_block['blockName'] ?? ''),
                (string) ($this->context['postId'] ?? ''),
                (string) ($this->context['postType'] ?? ''),
                (string) ($this->parsed_block['attrs']['layout']['type'] ?? '')
            );
        }
    }
}

// Blocks are built inline rather than by a shared helper: a test file that defines a function at file
// scope is the shape that cost this suite 49 tests once (DECISIONS 2026-07-23), and the constructor
// call is one line anyway.

it('renders the content of the page the template is rendering', function () {
    $block = new WP_Block(['blockName' => 'perego-theme/home-about'], ['postId' => 42, 'postType' => 'page']);

    expect((new HomeAboutRenderer())->render($block))
        ->toBe('[rendered core/post-content post=42 type=page layout=constrained]');
});

it('follows the queried page per language rather than pinning the English one', function () {
    // `/ar/` renders the Arabic front page, so the same template must ask for post 97 there.
    $block = new WP_Block(['blockName' => 'perego-theme/home-about'], ['postId' => 97, 'postType' => 'page']);

    expect((new HomeAboutRenderer())->render($block))->toContain('post=97');
});

it('falls back to the queried object when the block carries no context', function () {
    Functions\when('get_queried_object_id')->justReturn(42);
    Functions\when('get_post_type')->justReturn('page');

    expect((new HomeAboutRenderer())->render())->toContain('post=42');
});

it('renders nothing when there is no page to render', function () {
    Functions\when('get_queried_object_id')->justReturn(0);

    expect((new HomeAboutRenderer())->render())->toBe('');
});

it('renders nothing when the post type cannot be resolved', function () {
    Functions\when('get_post_type')->justReturn(false);

    $block = new WP_Block(['blockName' => 'perego-theme/home-about'], ['postId' => 42]);

    expect((new HomeAboutRenderer())->render($block))->toBe('');
});
