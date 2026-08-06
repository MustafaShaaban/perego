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

        public string $post_content = '';
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

/**
 * Stage post meta as `[postId => [key => value]]`.
 *
 * An unstaged key answers `''`, which is exactly what `get_post_meta` returns for one that was never
 * written — so a test only ever names the meta it actually cares about.
 *
 * @param array<int, array<string, mixed>> $byPost
 */
function perego_stage_meta(array $byPost): void
{
    Functions\when('get_post_meta')->alias(
        static fn (int $id, string $key) => $byPost[$id][$key] ?? ''
    );
}

/** Shorthand for the commonest staging: one client whose behaviour opens its gallery. */
function perego_lightbox_meta(int $postId, array $gallery, array $extra = []): void
{
    perego_stage_meta([$postId => array_merge([
        ClientPostType::META_BEHAVIOR => 'lightbox',
        ClientPostType::META_GALLERY => $gallery,
    ], $extra)]);
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
    Functions\when('do_blocks')->returnArg();
    perego_stage_meta([21 => [ClientPostType::META_STAT => 'Example stat']]);

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

it('renders a corporate card labelled by the client name, never printing it as visible text', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="corp-card"'))->toBe(2)
        ->and($html)->toContain('aria-label="Sample Corporate Client A"')
        ->and($html)->not->toContain('>Sample Corporate Client A<');
});

/*
 * The tile shows the client's LOGO. The equalizer glyph is a placeholder for a client that has not
 * been given artwork yet — a finished-looking tile rather than an empty box (owner, 2026-07-28).
 */
it('shows the equalizer placeholder only while a corporate client has no logo', function () {
    expect(renderClients())->toContain('class="eq-icon"');
});

// The stub renders whatever attributes the renderer asked for — the individual card passes no
// `class`, so this is shared by both card types and must not assume one.
it('shows the uploaded logo instead of the placeholder once one exists', function () {
    Functions\when('has_post_thumbnail')->justReturn(true);
    Functions\when('get_the_post_thumbnail')->alias(
        fn ($id, $size, $attr) => '<img class="' . ($attr['class'] ?? '') . '" src="https://perego.local/uploads/logo.png" />'
    );

    $html = renderClients();

    expect($html)->toContain('<img class="corp-card__logo" src="https://perego.local/uploads/logo.png"')
        ->and($html)->not->toContain('class="eq-icon"');
});

// The name is already the tile's aria-label; describing the logo too would announce it twice.
it('renders the corporate logo as decorative, the name carrying the accessible label', function () {
    Functions\when('has_post_thumbnail')->justReturn(true);
    Functions\when('get_the_post_thumbnail')->alias(
        fn ($id, $size, $attr) => '<img alt="' . ($attr['alt'] ?? 'MISSING') . '" loading="' . ($attr['loading'] ?? 'MISSING') . '" />'
    );

    expect(renderClients())->toContain('<img alt="" loading="lazy" />');
});

/*
 * Polylang gives a translation its own empty thumbnail slot, so without the English fallback an
 * Arabic tile would drop back to the placeholder its English twin never shows.
 */
it('falls back to the English logo for a translated corporate client', function () {
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 11 && $locale === 'en' ? 110 : 0);
    Functions\when('has_post_thumbnail')->alias(fn (int $id) => $id === 110);
    Functions\when('get_the_post_thumbnail')->alias(
        fn ($id) => $id === 110 ? '<img src="https://perego.local/uploads/en-logo.png" alt="" />' : ''
    );

    expect(renderClients())->toContain('en-logo.png');
});

/*
 * The behaviour model (owner decision 2026-07-28). A card acts ONLY when its behaviour says so — the
 * old inferences are gone: a corporate tile no longer opens its own logo just because it has one, and
 * "no behaviour set" is a static tile rather than a lightbox trigger.
 */

it('renders a corporate tile as an inert div when its behaviour is the default "no actions"', function () {
    // The tile has a logo, which used to be enough to make it a lightbox button on its own.
    Functions\when('has_post_thumbnail')->justReturn(true);
    Functions\when('get_the_post_thumbnail_url')->justReturn('https://perego.local/uploads/client-logo.png');

    $html = renderClients();

    expect($html)->toContain('<div class="corp-card" role="img"')
        ->and($html)->not->toContain('data-image=')
        ->and($html)->not->toContain('<button type="button" class="corp-card"');
});

it('opens the lightbox on the tile\'s own logo when the behaviour is lightbox but no gallery is set yet', function () {
    Functions\when('has_post_thumbnail')->justReturn(true);
    Functions\when('get_the_post_thumbnail_url')->justReturn('https://perego.local/uploads/client-logo.png');
    perego_stage_meta([11 => [ClientPostType::META_BEHAVIOR => 'lightbox']]);

    $html = renderClients();

    expect($html)->toContain('data-image="https://perego.local/uploads/client-logo.png"')
        ->and($html)->toContain('<button type="button" class="corp-card"');
});

it('degrades a lightbox tile with neither gallery nor logo to an inert div rather than a dead button', function () {
    perego_stage_meta([11 => [ClientPostType::META_BEHAVIOR => 'lightbox']]);

    $html = renderClients();

    expect($html)->toContain('<div class="corp-card" role="img"')
        ->and($html)->not->toContain('<button type="button" class="corp-card"');
});

it('opens the lightbox with a mixed image/video data-gallery list when a corporate client has a gallery', function () {
    perego_lightbox_meta(11, [
        ['type' => 'image', 'id' => 42, 'url' => ''],
        ['type' => 'video', 'id' => 0, 'url' => 'https://youtube.com/watch?v=abc'],
    ]);
    Functions\when('wp_get_attachment_image_url')->alias(fn (int $id) => $id === 42 ? 'https://perego.local/uploads/logo-42.png' : '');

    $html = renderClients();

    expect($html)->toContain('data-gallery="https://perego.local/uploads/logo-42.png,https://youtube.com/watch?v=abc"')
        ->and($html)->not->toContain('data-image=');
});

it('uses the single-item attribute rather than a one-entry gallery, so the lightbox skips its paging chrome', function () {
    perego_lightbox_meta(11, [['type' => 'video', 'id' => 0, 'url' => 'https://youtube.com/watch?v=abc']]);

    $html = renderClients();

    expect($html)->toContain('data-video="https://youtube.com/watch?v=abc"')
        ->and($html)->not->toContain('data-gallery=');
});

/*
 * `media-lightbox/view.js` splits `data-gallery` on commas, so a media URL containing one would be
 * torn into two broken slides. Percent-encoding is transparent to every URL parser and to the split.
 */
it('percent-encodes a comma inside a gallery URL so the list cannot be split mid-item', function () {
    perego_lightbox_meta(11, [
        ['type' => 'video', 'id' => 0, 'url' => 'https://cdn.test/a,b.mp4'],
        ['type' => 'video', 'id' => 0, 'url' => 'https://cdn.test/c.mp4'],
    ]);

    $html = renderClients();

    expect($html)->toContain('data-gallery="https://cdn.test/a%2Cb.mp4,https://cdn.test/c.mp4"');
});

it('navigates instead of opening the lightbox when the behaviour is link', function () {
    perego_stage_meta([11 => [
        ClientPostType::META_BEHAVIOR => 'link',
        ClientPostType::META_LINK_URL => 'https://client.example/',
        ClientPostType::META_LINK_NEW_TAB => '1',
    ]]);

    $html = renderClients();

    expect($html)->toContain('<a class="corp-card" href="https://client.example/" target="_blank" rel="noopener"')
        ->and($html)->not->toContain('data-gallery=')
        ->and($html)->not->toContain('data-image=');
});

it('opens a link in place when the editor did not ask for a new tab', function () {
    perego_stage_meta([11 => [
        ClientPostType::META_BEHAVIOR => 'link',
        ClientPostType::META_LINK_URL => 'https://client.example/',
    ]]);

    expect(renderClients())->toContain('<a class="corp-card" href="https://client.example/"')
        ->not->toContain('<a class="corp-card" href="https://client.example/" role=');
});

it('degrades a link behaviour with no destination to an inert div', function () {
    perego_stage_meta([11 => [ClientPostType::META_BEHAVIOR => 'link']]);

    $html = renderClients();

    expect($html)->toContain('<div class="corp-card" role="img"')
        ->and($html)->not->toContain('<a class="corp-card"');
});

it('renders an individual card with a visible name and a thumbnail placeholder when there is none', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="indiv-card__title"'))->toBe(1)
        ->and($html)->toContain('Sample Creator One')
        ->and($html)->toContain('client-review-crop.png');
});

it('renders a non-interactive card with no play affordance when the behaviour is the default "no actions"', function () {
    $html = renderClients();

    expect($html)->toContain('<div class="indiv-card"')
        ->and($html)->not->toContain('class="play-btn"')
        ->and($html)->not->toContain('data-video=');
});

/**
 * The inert individual card carries NO role, and that is the whole fix.
 *
 * It briefly carried `role="img"`, which axe reported as two serious `role-img-alt` violations
 * (home EN and AR) because nothing gave it a name. The name was the smaller half of the problem:
 * `role="img"` makes the element a single graphic to assistive technology, so the `<h3>` title,
 * the subtitle and the body copy *inside* it stop being reachable at all. An `aria-label` would
 * have satisfied axe and left the content hidden — a green check over a worse card.
 *
 * A plain `<div>` is right because the card's content is real text that reads itself. The lightbox
 * and link variants already carry no role and have always been fine, which is the shape this
 * restores.
 */
it('gives the inert individual card no role, so its heading and copy stay reachable', function () {
    $html = renderClients();

    expect($html)->toContain('<div class="indiv-card">')
        ->and($html)->not->toContain('<div class="indiv-card" role=')
        ->and($html)->toContain('<h3 class="indiv-card__title">Sample Creator One</h3>');
});

/**
 * The corporate tile keeps `role="img"`, and the contrast with the test above is the point: it is a
 * bare logo with `alt=""`, so it genuinely IS one graphic, and `corporateCard()` passes the client
 * name as `aria-label` — which is what `role-img-alt` asks for. Same inert branch, different role,
 * because the two cards hold different things.
 */
it('keeps role="img" on the inert corporate tile, which is a named graphic and nothing else', function () {
    $html = renderClients();

    expect($html)->toContain('<div class="corp-card" role="img" aria-label="Sample Corporate Client A">');
});

it('renders a lightbox client as a single-action button with the ▶ affordance (handoff C-04: never lightbox + navigation together)', function () {
    perego_lightbox_meta(21, [['type' => 'video', 'id' => 0, 'url' => 'https://www.youtube.com/embed/example']]);

    $html = renderClients();

    expect($html)->toContain('<button type="button" class="indiv-card"')
        ->and($html)->toContain('data-video="https://www.youtube.com/embed/example"')
        ->and($html)->not->toContain('<a class="indiv-card"') // no parallel navigation
        ->and($html)->toContain('class="play-btn" aria-hidden="true"');
});

it('pages an individual card through a multi-item gallery, the same as a corporate tile', function () {
    perego_lightbox_meta(21, [
        ['type' => 'video', 'id' => 0, 'url' => 'https://www.youtube.com/embed/example'],
        ['type' => 'video', 'id' => 7, 'url' => 'https://perego.local/uploads/clip.mp4'],
    ]);

    expect(renderClients())->toContain('data-gallery="https://www.youtube.com/embed/example,https://perego.local/uploads/clip.mp4"');
});

it('navigates instead of opening the lightbox when an individual client links out — and wears no ▶ (reserved for in-lightbox media)', function () {
    perego_stage_meta([21 => [
        ClientPostType::META_BEHAVIOR => 'link',
        ClientPostType::META_LINK_URL => 'https://vimeo.com/example',
        ClientPostType::META_LINK_NEW_TAB => '1',
    ]]);

    $html = renderClients();

    expect($html)->toContain('<a class="indiv-card" href="https://vimeo.com/example" target="_blank" rel="noopener"')
        ->and($html)->not->toContain('data-video=')
        ->and($html)->not->toContain('class="play-btn"');
});

/*
 * The badge moved from one carousel-wide block attribute to each client's own setting, so a row can
 * mix badged and unbadged cards. It is stored inverted — see `ClientPostType::META_HIDE_PLAY_ICON`.
 */
it('hides the play badge for the one client that asked to, leaving the card interactive', function () {
    perego_lightbox_meta(21, [['type' => 'video', 'id' => 0, 'url' => 'https://www.youtube.com/embed/example']], [
        ClientPostType::META_HIDE_PLAY_ICON => '1',
    ]);

    $html = renderClients();

    expect($html)->not->toContain('class="play-btn"')
        ->and($html)->toContain('data-video="https://www.youtube.com/embed/example"');
});

it('keeps the badge for a client saved before the setting existed', function () {
    perego_lightbox_meta(21, [['type' => 'video', 'id' => 0, 'url' => 'https://www.youtube.com/embed/example']]);

    expect(renderClients())->toContain('class="play-btn"');
});

it('shows the card subtitle only when one is set', function () {
    $html = renderClients();

    expect(substr_count($html, 'class="indiv-card__sub"'))->toBe(1)
        ->and($html)->toContain('Example stat');
});

it('omits the subtitle line when none is set', function () {
    perego_stage_meta([]);

    expect(renderClients())->not->toContain('class="indiv-card__sub"');
});

it('keeps inline <strong> in the card subtitle so the number can be bold (handoff "+1M")', function () {
    perego_stage_meta([21 => [ClientPostType::META_STAT => '<strong>+1M</strong> views']]);

    expect(renderClients())->toContain('<p class="indiv-card__sub"><strong>+1M</strong> views</p>');
});

/*
 * The card's body copy is the client post's own editor content (owner decision 2026-07-28), replacing
 * the retired `_perego_client_sub` meta field.
 */
it('renders the client post content as the card body', function () {
    $GLOBALS['__perego_clients_by_term_id'][PEREGO_TEST_INDIV_EN][0]->post_content = '<p>intertainment show</p>';

    $html = renderClients();

    expect($html)->toContain('<div class="indiv-card__content"><p>intertainment show</p></div>');
});

it('omits the body entirely when the post has no content', function () {
    expect(renderClients())->not->toContain('indiv-card__content');
});

/*
 * The card element is itself a <button> or an <a>, so a link or an embed nested inside it is both
 * invalid HTML and a real accessibility defect — and editors write this body in the normal editor,
 * where any block is available.
 */
it('allows only non-interactive markup in the card body', function () {
    // Assert the allowlist the body is filtered through, not a re-implementation of `wp_kses` —
    // the same choice `ClientPostTypeTest` makes for the subtitle, and for the same reason: a fake
    // kses can drift from the real one and start passing for the wrong reason.
    $captured = null;
    Functions\when('wp_kses')->alias(function (string $raw, array $allowed) use (&$captured) {
        $captured = $allowed;

        return $raw;
    });
    $GLOBALS['__perego_clients_by_term_id'][PEREGO_TEST_INDIV_EN][0]->post_content = '<p>Safe copy</p>';

    $html = renderClients();

    expect($html)->toContain('<div class="indiv-card__content"><p>Safe copy</p></div>')
        ->and(array_keys($captured))->toContain('p', 'strong', 'em', 'ul', 'li')
        // The card element is itself a <button>/<a>: a nested control is invalid HTML and a real
        // screen-reader defect, and an iframe inside a carousel card is never wanted.
        ->and(array_keys($captured))->not->toContain('a')
        ->and(array_keys($captured))->not->toContain('iframe')
        ->and(array_keys($captured))->not->toContain('button')
        ->and(array_keys($captured))->not->toContain('video');
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

    expect($html)->toContain('id="corporateTrack" tabindex="0" role="group"')
        ->and($html)->toContain('id="individualTrack" tabindex="0" role="group"')
        ->and($html)->toContain('aria-label="Previous clients"')
        ->and($html)->toContain('aria-label="More clients"')
        ->and($html)->toContain('<circle class="eq-bar"')
        ->and($html)->toContain('<svg viewBox="0 0 24 24" aria-hidden="true">');
});

it('keeps buttons and links in the carousel on their valid native roles', function () {
    Functions\when('has_post_thumbnail')->justReturn(true);
    Functions\when('get_the_post_thumbnail_url')->justReturn('https://perego.local/client.png');
    perego_stage_meta([
        11 => [ClientPostType::META_BEHAVIOR => 'lightbox'],
        21 => [
            ClientPostType::META_BEHAVIOR => 'link',
            ClientPostType::META_LINK_URL => 'https://creator.example/',
        ],
    ]);

    $html = renderClients();

    expect($html)->toContain('<button type="button" class="corp-card"')
        ->and($html)->toContain('<a class="indiv-card" href="https://creator.example/"')
        ->and($html)->not->toContain('role="listitem"');
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

/*
 * Polylang does not copy post meta to translations, so an Arabic client post has its own EMPTY meta
 * while the English record holds the real values. The renderer used to read the translated post
 * directly, so on the live Arabic homepage all three real client cards rendered as inert
 * `<div class="indiv-card">` — they looked correct and did nothing when clicked, while the identical
 * English cards were `<button data-video>` lightbox triggers. These pin the English fallback.
 */

it('resolves an Arabic client behaviour and gallery from its linked English record, so the AR card opens the lightbox', function () {
    // Client 21 is the AR post (no meta of its own); 210 is its linked English translation.
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 21 && $locale === 'en' ? 210 : 0);
    perego_stage_meta([210 => [
        ClientPostType::META_BEHAVIOR => 'lightbox',
        ClientPostType::META_GALLERY => [['type' => 'video', 'id' => 0, 'url' => 'https://www.youtube.com/embed/from-english']],
        // Editorial copy on the English record — must NOT be inherited (see the next test).
        ClientPostType::META_STAT => 'English subtitle',
    ]]);

    $html = renderClients();

    expect($html)->toContain('<button type="button" class="indiv-card" data-video="https://www.youtube.com/embed/from-english"')
        // The exact defect: no inert div is left behind for a client whose EN record has media.
        ->and($html)->not->toContain('<div class="indiv-card" role="listitem"');
});

it('does NOT inherit the English subtitle — media crosses languages, editorial copy does not', function () {
    // The first cut of this fix pulled the copy through the same fallback, which printed the English
    // wording (live on the EN records) onto Arabic cards — the very language leak this pass removes.
    // An untranslated card shows no subtitle and still opens its media.
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 21 && $locale === 'en' ? 210 : 0);
    perego_stage_meta([210 => [
        ClientPostType::META_BEHAVIOR => 'lightbox',
        ClientPostType::META_GALLERY => [['type' => 'video', 'id' => 0, 'url' => 'https://www.youtube.com/embed/from-english']],
        ClientPostType::META_STAT => '<strong>+1M</strong> views',
    ]]);

    $html = renderClients();

    expect($html)->toContain('data-video="https://www.youtube.com/embed/from-english"')
        ->and($html)->not->toContain('+1M');
});

it('prefers the translated post own gallery over the English record when it has one', function () {
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 21 && $locale === 'en' ? 210 : 0);
    perego_stage_meta([
        21 => [
            ClientPostType::META_BEHAVIOR => 'lightbox',
            ClientPostType::META_GALLERY => [['type' => 'video', 'id' => 0, 'url' => 'https://www.youtube.com/embed/arabic-own']],
        ],
        210 => [
            ClientPostType::META_BEHAVIOR => 'lightbox',
            ClientPostType::META_GALLERY => [['type' => 'video', 'id' => 0, 'url' => 'https://www.youtube.com/embed/from-english']],
        ],
    ]);

    $html = renderClients();

    expect($html)->toContain('embed/arabic-own')
        ->and($html)->not->toContain('embed/from-english');
});

it('falls back to the English featured image when a translated client has none', function () {
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 21 && $locale === 'en' ? 210 : 0);
    Functions\when('has_post_thumbnail')->alias(fn (int $id) => $id === 210);
    Functions\when('get_the_post_thumbnail')->alias(
        fn (int $id) => $id === 210 ? '<img src="https://perego.local/uploads/en-logo.png" alt="" />' : ''
    );

    expect(renderClients())->toContain('en-logo.png');
});

it('stays inert when neither the translation nor its English record chose a behaviour', function () {
    Functions\when('pll_get_post')->alias(fn (int $id, string $locale) => $id === 21 && $locale === 'en' ? 210 : 0);
    perego_stage_meta([]);

    $html = renderClients();

    expect($html)->toContain('<div class="indiv-card"')
        ->and($html)->not->toContain('data-video=');
});
