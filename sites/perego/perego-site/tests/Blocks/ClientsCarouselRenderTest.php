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
    Functions\when('__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
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
        ->and(substr_count($html, 'class="corp-slider reveal"'))->toBe(1)
        ->and(substr_count($html, 'class="indiv-slider reveal"'))->toBe(1)
        ->and($html)->toContain('Corporate Clients')
        ->and($html)->toContain('Individual Clients');
});

it('renders a corporate card as an icon tile labelled by the client name', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="corp-card"'))->toBe(2)
        ->and($html)->toContain('aria-label="Sample Corporate Client A"')
        ->and($html)->toContain('class="eq-icon"')
        ->and($html)->not->toContain('>Sample Corporate Client A<');
});

it('renders an individual card with a visible name and a thumbnail placeholder when there is none', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="indiv-card__title"'))->toBe(1)
        ->and($html)->toContain('Sample Creator One')
        ->and($html)->toContain('client-review-crop.png');
});

it('shows the client stat only when one is set', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="indiv-card__stat"'))->toBe(1)
        ->and($html)->toContain('Example stat');
});

it('renders the handoff navigation controls for populated tracks', function () {
    $html = renderClients();

    expect(substr_count($html, 'corp-arrow corp-arrow--prev'))->toBe(2)
        ->and(substr_count($html, 'corp-arrow corp-arrow--next'))->toBe(2)
        ->and($html)->toContain('class="corp-track"')
        ->and($html)->toContain('class="indiv-track"');
});

it('preserves the handoff track identifiers, labels, and arrow SVG controls', function () {
    $html = renderClients();

    expect($html)->toContain('id="corporateTrack" tabindex="0" role="group"')
        ->and($html)->toContain('id="individualTrack" role="list"')
        ->and($html)->toContain('aria-label="Previous clients"')
        ->and($html)->toContain('aria-label="More clients"')
        ->and($html)->toContain('<circle class="eq-bar"')
        ->and($html)->toContain('<svg viewBox="0 0 24 24" aria-hidden="true">');
});

it('omits the handoff track shell for a client type with no posts, keeping the heading', function () {
    $GLOBALS['__perego_clients_by_term_id'] = [PEREGO_TEST_CORP_EN => [], PEREGO_TEST_INDIV_EN => []];

    $html = renderClients();

    expect($html)->toContain('Corporate Clients')
        ->and(substr_count($html, 'corp-slider reveal'))->toBe(0)
        ->and(substr_count($html, 'indiv-card__title'))->toBe(0);
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

    expect(substr_count($html, 'class="corp-card"'))->toBe(1)
        ->and(substr_count($html, 'class="indiv-card"'))->toBe(1);
});
