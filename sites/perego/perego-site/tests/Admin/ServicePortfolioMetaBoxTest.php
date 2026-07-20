<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Admin\ServicePortfolioMetaBox;
use PeregoSite\PostTypes\ServicePostType;

beforeEach(function () {
    Functions\when('wp_unslash')->returnArg();
    Functions\when('sanitize_text_field')->returnArg();
    Functions\when('wp_is_post_revision')->justReturn(false);
});

function servicePortfolioEditorPost(): object
{
    $post = new WP_Post();
    $post->ID = 82;
    $post->post_type = ServicePostType::POST_TYPE;

    return $post;
}

/** @return array{updated:array<string,mixed>,deleted:list<string>} */
function saveServicePortfolio(array $data): array
{
    $updated = [];
    $deleted = [];
    Functions\when('update_post_meta')->alias(function ($id, $key, $value) use (&$updated) {
        $updated[$key] = $value;

        return true;
    });
    Functions\when('delete_post_meta')->alias(function ($id, $key) use (&$deleted) {
        $deleted[] = $key;

        return true;
    });
    $_POST = $data;
    (new ServicePortfolioMetaBox())->save(82, servicePortfolioEditorPost());
    $_POST = [];

    return ['updated' => $updated, 'deleted' => $deleted];
}

it('saves a manual Project order, exclusions, and normalized portfolio mode', function () {
    Functions\when('wp_verify_nonce')->justReturn(true);
    Functions\when('current_user_can')->justReturn(true);

    $result = saveServicePortfolio([
        'perego_service_portfolio_nonce' => 'nonce',
        ServicePostType::META_PORTFOLIO_MODE => 'hybrid',
        'perego_service_portfolio_projects' => ['9', 4, 9, 0],
        'perego_service_portfolio_exclusions' => [7, '3'],
    ]);

    expect($result['updated'][ServicePostType::META_PORTFOLIO_MODE])->toBe('hybrid')
        ->and($result['updated'][ServicePostType::META_PORTFOLIO_PROJECT_IDS])->toBe([9, 4])
        ->and($result['updated'][ServicePostType::META_PORTFOLIO_EXCLUDE_IDS])->toBe([7, 3]);
});

it('does not save without the nonce or edit capability', function () {
    Functions\when('wp_verify_nonce')->justReturn(false);
    Functions\when('current_user_can')->justReturn(true);

    expect(saveServicePortfolio(['perego_service_portfolio_nonce' => 'bad'])['updated'])->toBe([]);
});
