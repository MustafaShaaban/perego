<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

use WP_Block;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego-theme/home-about block: the homepage About panels.
 *
 * The copy is **not** block attributes. It is the front page's own `post_content` — spec 004 T012 moved
 * it out of a PHP provider precisely so it would be real, editable page content, and each language keeps
 * its own page (EN and its Polylang translation). This block exists for the editor: `wp:post-content`
 * renders nothing while a *template* is being edited, because `render_block_core_post_content()` returns
 * early without a `postId` context, so the Front Page canvas could only show core's generic placeholder.
 * The block's `edit()` binds that canvas to the front page instead.
 *
 * On the front end it therefore has to be `wp:post-content` — so it renders the real thing rather than a
 * copy of it. Rebuilding the markup by hand would mean hardcoding `entry-content wp-block-post-content
 * has-global-padding is-layout-constrained …`, and those classes are not literals in core: they are
 * computed by `get_block_wrapper_attributes()` from the block's registered layout support, and would
 * drift the first time core changed one.
 *
 * Language needs no special handling. `render_block()` seeds `postId` from the global post, so `/` passes
 * the English front page and `/ar/` passes its Arabic translation, each rendering its own panels.
 */
final class HomeAboutRenderer
{
    /** Mirrors the `{"layout":{"type":"constrained"}}` the template's `wp:post-content` carried. */
    private const POST_CONTENT_ATTRS = ['layout' => ['type' => 'constrained']];

    public function render(?WP_Block $block = null): string
    {
        $postId = (int) ($block->context['postId'] ?? get_queried_object_id());

        if ($postId <= 0) {
            return '';
        }

        $postType = (string) ($block->context['postType'] ?? get_post_type($postId));

        if ($postType === '') {
            return '';
        }

        $postContent = new WP_Block(
            [
                'blockName'    => 'core/post-content',
                'attrs'        => self::POST_CONTENT_ATTRS,
                'innerBlocks'  => [],
                'innerHTML'    => '',
                'innerContent' => [],
            ],
            ['postId' => $postId, 'postType' => $postType]
        );

        return $postContent->render();
    }
}
