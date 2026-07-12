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

// A headless WP_Query stub: the renderer only reads ->posts. It resolves the requested client type
// from the tax_query term and returns whatever the current test staged in $GLOBALS.
if (! class_exists('WP_Query')) {
    class WP_Query
    {
        /** @var array<int, object> */
        public array $posts = [];

        public function __construct(array $args = [])
        {
            $type = $args['tax_query'][0]['terms'] ?? '';
            $this->posts = $GLOBALS['__perego_clients_by_type'][$type] ?? [];
        }
    }
}

function perego_client_post(int $id): WP_Post
{
    $post = new WP_Post();
    $post->ID = $id;

    return $post;
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

    $GLOBALS['__perego_clients_by_type'] = [
        'corporate' => [perego_client_post(11), perego_client_post(12)],
        'individual' => [perego_client_post(21)],
    ];
});

afterEach(function () {
    unset($GLOBALS['__perego_clients_by_type']);
});

function renderClients(string $locale = 'en'): string
{
    return (new ClientsCarouselRenderer(new ClientsContent($locale)))->render();
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
    $GLOBALS['__perego_clients_by_type'] = ['corporate' => [], 'individual' => []];

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
