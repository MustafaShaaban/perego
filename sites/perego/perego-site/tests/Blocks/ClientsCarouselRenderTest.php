<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\Blocks\ClientsCarouselRenderer;
use PeregoSite\Content\ClientsContent;
use PeregoSite\PostTypes\ClientPostType;

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
            $posts = $GLOBALS['__perego_clients_by_term_id'][$termId] ?? [];
            $perPage = (int) ($args['posts_per_page'] ?? -1);
            $this->posts = $perPage > 0 ? array_slice($posts, 0, $perPage) : $posts;
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
    Functions\when('wp_kses')->returnArg();
    Functions\when('wp_kses_post')->returnArg();
    Functions\when('esc_attr')->returnArg();
    Functions\when('esc_attr__')->returnArg();
    Functions\when('__')->returnArg();
    Functions\when('esc_url')->returnArg();
    Functions\when('get_stylesheet_directory_uri')->justReturn('https://perego.local/wp-content/themes/perego-theme');
    Functions\when('has_post_thumbnail')->justReturn(false);
    Functions\when('get_the_post_thumbnail')->justReturn('');
    Functions\when('get_the_post_thumbnail_url')->justReturn('');
    Functions\when('wp_get_attachment_image_url')->justReturn('');
    Functions\when('absint')->alias(fn ($v) => abs((int) $v));
    Functions\when('esc_url_raw')->returnArg();
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

/** @param array<string,string> $attributes */
function renderClients(string $locale = 'en', array $attributes = []): string
{
    return (new ClientsCarouselRenderer(new ClientsContent($locale), $locale, $attributes))->render();
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

it('falls back to the locale-aware ClientsContent seed when no block attribute was ever set', function () {
    $htmlEn = renderClients('en');
    $htmlAr = renderClients('ar');

    expect($htmlEn)->toContain('Corporate Clients')->toContain('Individual Clients')
        ->and($htmlAr)->toContain('عملاء الشركات')->toContain('عملاء أفراد');
});

it('prefers the editor-set En/Ar block attribute over the seed, per locale', function () {
    $attributes = [
        'corporateHeadingEn' => 'Our Corporate Partners',
        'corporateHeadingAr' => 'شركاؤنا',
    ];

    $htmlEn = renderClients('en', $attributes);
    $htmlAr = renderClients('ar', $attributes);

    expect($htmlEn)->toContain('Our Corporate Partners')->not->toContain('Corporate Clients')
        ->and($htmlAr)->toContain('شركاؤنا')->not->toContain('عملاء الشركات');
});

it('renders a corporate card as an icon tile labelled by the client name', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="corp-card"'))->toBe(2)
        ->and($html)->toContain('aria-label="Sample Corporate Client A"')
        ->and($html)->toContain('class="eq-icon"')
        ->and($html)->not->toContain('>Sample Corporate Client A<');
});

it('is not wired to the media lightbox when a corporate client has no logo image', function () {
    $html = renderClients();

    expect($html)->not->toContain('data-image=');
});

it('opens the site-wide media lightbox when a corporate client has a logo image', function () {
    Functions\when('has_post_thumbnail')->justReturn(true);
    Functions\when('get_the_post_thumbnail_url')->justReturn('https://perego.local/wp-content/uploads/client-logo.png');

    $html = renderClients();

    expect($html)->toContain('data-image="https://perego.local/wp-content/uploads/client-logo.png"');
});

it('opens the lightbox with a mixed image/video data-gallery list when a corporate client has a gallery', function () {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => match (true) {
        $id === 11 && $key === ClientPostType::META_GALLERY => [
            ['type' => 'image', 'id' => 42, 'url' => ''],
            ['type' => 'video', 'id' => 0, 'url' => 'https://youtube.com/watch?v=abc'],
        ],
        default => '',
    });
    Functions\when('wp_get_attachment_image_url')->alias(fn (int $id) => $id === 42 ? 'https://perego.local/uploads/logo-42.png' : '');

    $html = renderClients();

    expect($html)->toContain('data-gallery="https://perego.local/uploads/logo-42.png,https://youtube.com/watch?v=abc"')
        ->and($html)->not->toContain('data-image=');
});

it('prefers the gallery over the single logo image when a corporate client has both', function () {
    // corporateCard()'s if/else is mutually exclusive: a rendered `data-gallery` on client 11's tile
    // is itself the proof the logo branch (`data-image`) was skipped for that client, even though
    // client 12 (no gallery, same mocked thumbnail) legitimately still falls back to it.
    Functions\when('has_post_thumbnail')->justReturn(true);
    Functions\when('get_the_post_thumbnail_url')->justReturn('https://perego.local/uploads/logo.png');
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => match (true) {
        $id === 11 && $key === ClientPostType::META_GALLERY => [['type' => 'video', 'id' => 0, 'url' => 'https://youtube.com/watch?v=abc']],
        default => '',
    });

    $html = renderClients();

    expect($html)->toContain('data-gallery="https://youtube.com/watch?v=abc"')
        ->and($html)->toContain('data-image="https://perego.local/uploads/logo.png"'); // client 12's legitimate fallback
});

it('renders an individual card with a visible name and a thumbnail placeholder when there is none', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="indiv-card__title"'))->toBe(1)
        ->and($html)->toContain('Sample Creator One')
        ->and($html)->toContain('client-review-crop.png');
});

it('renders a non-interactive card with no play affordance when a client has no video URL', function () {
    $html = renderClients();

    expect($html)->toContain('<div class="indiv-card"')
        ->and($html)->not->toContain('class="play-btn"')
        ->and($html)->not->toContain('data-video=');
});

it('renders an embed-video client as a single-action lightbox button with the ▶ affordance (handoff C-04: never lightbox + navigation together)', function () {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => match (true) {
        $id === 21 && $key === '_perego_client_stat' => 'Example stat',
        $id === 21 && $key === ClientPostType::META_VIDEO_URL => 'https://www.youtube.com/embed/example',
        default => '',
    });

    $html = renderClients();

    expect($html)->toContain('<button type="button" class="indiv-card"')
        ->and($html)->toContain('data-video="https://www.youtube.com/embed/example"')
        ->and($html)->not->toContain('<a class="indiv-card"') // no parallel navigation
        ->and($html)->toContain('class="play-btn" aria-hidden="true"');
});

it('opens the lightbox for an uploaded video the same way as an embed', function () {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => match (true) {
        $id === 21 && $key === ClientPostType::META_VIDEO_URL => 'https://perego.local/uploads/clip.mp4',
        $id === 21 && $key === ClientPostType::META_VIDEO_TYPE => 'upload',
        default => '',
    });

    $html = renderClients();

    expect($html)->toContain('data-video="https://perego.local/uploads/clip.mp4"');
});

it('opens an external video link in a new tab instead of the lightbox — no data-video and no play affordance (▶ is reserved for in-lightbox media)', function () {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => match (true) {
        $id === 21 && $key === ClientPostType::META_VIDEO_URL => 'https://vimeo.com/example',
        $id === 21 && $key === ClientPostType::META_VIDEO_TYPE => 'external',
        default => '',
    });

    $html = renderClients();

    expect($html)->toContain('<a class="indiv-card" href="https://vimeo.com/example" target="_blank" rel="noopener"')
        ->and($html)->not->toContain('data-video=')
        ->and($html)->not->toContain('class="play-btn"');
});

it('shows the client stat only when one is set', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="indiv-card__stat"'))->toBe(1)
        ->and($html)->toContain('Example stat');
});

it('renders the individual card subtitle line only when a subtitle is set', function () {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => $id === 21 && $key === ClientPostType::META_SUB ? 'intertainment show' : '');

    $html = renderClients();

    expect(substr_count($html, 'class="indiv-card__sub"'))->toBe(1)
        ->and($html)->toContain('<p class="indiv-card__sub">intertainment show</p>');
});

it('omits the subtitle line when no subtitle is set', function () {
    $html = renderClients();

    expect($html)->not->toContain('class="indiv-card__sub"');
});

it('keeps inline <strong> in the individual card stat so the number can be bold (handoff "+1M")', function () {
    Functions\when('get_post_meta')->alias(fn (int $id, string $key) => $id === 21 && $key === ClientPostType::META_STAT ? '<strong>+1M</strong> views' : '');

    $html = renderClients();

    expect($html)->toContain('<p class="indiv-card__stat"><strong>+1M</strong> views</p>');
});

it('caps the corporate carousel at 20 tiles even when more clients exist', function () {
    $many = [];
    for ($i = 1; $i <= 25; $i++) {
        $many[] = perego_client_post(100 + $i);
    }
    $GLOBALS['__perego_clients_by_term_id'][PEREGO_TEST_CORP_EN] = $many;

    $html = renderClients();

    expect(substr_count($html, 'class="corp-card"'))->toBe(20);
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

    expect($html)->toContain('id="corporateTrack" tabindex="0" role="list"')
        ->and($html)->toContain('id="individualTrack" tabindex="0" role="list"')
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

it('uses the manually ordered corporate client selection when requested', function () {
    $client = perego_client_post(91);
    $client->post_type = ClientPostType::POST_TYPE;
    $client->post_status = 'publish';

    Functions\when('get_post')->alias(fn (int $id) => $id === 91 ? $client : null);
    Functions\when('get_the_title')->alias(fn (WP_Post $post) => $post->ID === 91 ? 'Chosen client' : '');

    $html = renderClients('en', [
        'corporateMode' => 'manual',
        'corporateOrder' => [91],
        'individualMode' => 'manual',
        'individualOrder' => [],
    ]);

    expect(substr_count($html, 'class="corp-card"'))->toBe(1)
        ->and($html)->toContain('aria-label="Chosen client"')
        ->and(substr_count($html, 'class="indiv-card"'))->toBe(0);
});
