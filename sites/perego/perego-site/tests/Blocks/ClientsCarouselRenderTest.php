<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ClientsCarouselRenderer;
use PeregoSite\Content\ClientsContent;

// Minimal WP_Post stub (shared shape with the other block tests — guarded so first definition wins).
if (! class_exists('WP_Post')) {
    class WP_Post
    {
        public int $ID = 0;

        public string $post_name = '';
    }
}

// A headless WP_Query stub: the renderer only reads ->posts. It resolves the requested (locale-
// specific) term id from the tax_query and returns whatever the current test staged in $GLOBALS.
if (! class_exists('WP_Query')) {
    class WP_Query
    {
        /** @var array<int, object> */
        public array $posts = [];

        public function __construct(array $args = [])
        {
            $termId = $args['tax_query'][0]['terms'] ?? 0;
            $this->posts = $GLOBALS['__perego_clients_by_term_id'][$termId] ?? [];
        }
    }
}

// EN term ids for 'corporate'/'individual', and their AR Polylang-translation counterparts.
const PEREGO_TEST_CORP_EN = 30;
const PEREGO_TEST_INDIV_EN = 32;
const PEREGO_TEST_CORP_AR = 72;
const PEREGO_TEST_INDIV_AR = 74;

function perego_client_post(int $id): WP_Post
{
    $post = new WP_Post();
    $post->ID = $id;

    return $post;
}

/** A minimal stdClass term stub carrying only the property the renderer reads: term_id. */
function perego_term_stub(int $termId): object
{
    return (object) ['term_id' => $termId];
}

beforeEach(function () {
    Functions\when('esc_html')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('has_post_thumbnail')->justReturn(false);
    Functions\when('get_the_post_thumbnail')->justReturn('');
    Functions\when('wp_reset_postdata')->justReturn(null);
    Functions\when('get_the_title')->alias(fn ($post) => [
        11 => 'Sample Corporate Client A',
        12 => 'Sample Corporate Client B',
        21 => 'Sample Creator One',
    ][$post->ID] ?? '');
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => $id === 21 && $key === '_perego_client_stat' ? 'Example stat' : '');

    // perego_client_type EN terms, each with an AR Polylang-translation counterpart.
    Functions\when('get_term_by')->alias(fn (string $field, string $slug) => match ($slug) {
        'corporate' => perego_term_stub(PEREGO_TEST_CORP_EN),
        'individual' => perego_term_stub(PEREGO_TEST_INDIV_EN),
        default => false,
    });
    Functions\when('pll_get_term')->alias(function (int $termId, string $locale) {
        if ($locale !== 'ar') {
            return $termId;
        }

        if ($termId === PEREGO_TEST_CORP_EN) {
            return PEREGO_TEST_CORP_AR;
        }

        if ($termId === PEREGO_TEST_INDIV_EN) {
            return PEREGO_TEST_INDIV_AR;
        }

        return $termId;
    });

    $GLOBALS['__perego_clients_by_term_id'] = [
        PEREGO_TEST_CORP_EN => [perego_client_post(11), perego_client_post(12)],
        PEREGO_TEST_INDIV_EN => [perego_client_post(21)],
        PEREGO_TEST_CORP_AR => [perego_client_post(31)],
        PEREGO_TEST_INDIV_AR => [perego_client_post(32)],
    ];
});

afterEach(function () {
    unset($GLOBALS['__perego_clients_by_term_id']);
});

function renderClients(string $locale = 'en'): string
{
    return (new ClientsCarouselRenderer(new ClientsContent($locale), $locale))->render();
}

it('renders one #clients section with the two carousels and no competing H1', function () {
    $html = renderClients();

    expect(substr_count($html, 'id="clients"'))->toBe(1)
        ->and(substr_count($html, '<h1'))->toBe(0)
        ->and(substr_count($html, 'data-clients-swiper'))->toBe(2)
        ->and($html)->toContain('Corporate Clients')
        ->and($html)->toContain('Individual Clients');
});

it('renders a card per client with the name and a placeholder when there is no thumbnail', function () {
    $html = renderClients();

    expect(substr_count($html, 'client-card__name'))->toBe(3)
        ->and($html)->toContain('Sample Corporate Client A')
        ->and($html)->toContain('Sample Creator One')
        ->and($html)->toContain('client-card__media--placeholder');
});

it('shows the client stat only when one is set', function () {
    $html = renderClients();

    expect(substr_count($html, 'client-card__stat'))->toBe(1)
        ->and($html)->toContain('Example stat');
});

it('renders the swiper navigation controls and pagination for a populated carousel', function () {
    $html = renderClients();

    expect($html)->toContain('swiper-button-prev')
        ->and($html)->toContain('swiper-button-next')
        ->and($html)->toContain('swiper-pagination');
});

it('omits the swiper shell for a client type with no posts, keeping the heading', function () {
    $GLOBALS['__perego_clients_by_term_id'] = [PEREGO_TEST_CORP_EN => [], PEREGO_TEST_INDIV_EN => []];

    $html = renderClients();

    expect($html)->toContain('Corporate Clients')
        ->and(substr_count($html, 'data-clients-swiper'))->toBe(0)
        ->and(substr_count($html, 'client-card__name'))->toBe(0);
});

it('localizes the section into Arabic', function () {
    $html = renderClients('ar');

    expect($html)->toContain('عملاء الشركات')
        ->and($html)->toContain('عملاء أفراد');
});

it('queries the Arabic-language taxonomy term, not the English one, on the Arabic route', function () {
    // Regression test: the renderer used to hardcode the English term slug in every locale, so the
    // Arabic carousels always queried zero posts even when AR-tagged client posts existed.
    $html = renderClients('ar');

    expect(substr_count($html, 'client-card__name'))->toBe(2);
});
