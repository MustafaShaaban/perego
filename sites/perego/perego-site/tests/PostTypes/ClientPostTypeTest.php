<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

use Brain\Monkey\Functions;
use PeregoSite\PostTypes\ClientPostType;

beforeEach(function () {
    Functions\when('__')->returnArg();
});

it('registers a public client post type with REST + thumbnail support and no archive', function () {
    $args = (new ClientPostType())->postTypeArgs();

    expect($args['public'])->toBeTrue()
        ->and($args['has_archive'])->toBeFalse()
        ->and($args['show_in_rest'])->toBeTrue()
        ->and($args['supports'])->toContain('thumbnail')
        ->and($args['supports'])->toContain('editor')
        ->and($args['rewrite']['slug'])->toBe('clients');
});

it('defines a hierarchical, REST-exposed client-type taxonomy', function () {
    $args = (new ClientPostType())->taxonomyArgs();

    expect($args['hierarchical'])->toBeTrue()
        ->and($args['show_in_rest'])->toBeTrue()
        ->and($args['public'])->toBeTrue();
});

it('names the two client types in display order', function () {
    expect(array_keys(ClientPostType::TYPES))->toBe(['corporate', 'individual'])
        ->and(ClientPostType::POST_TYPE)->toBe('perego_client')
        ->and(ClientPostType::TAXONOMY)->toBe('perego_client_type');
});

it('registers the post type and taxonomy with their canonical slugs on register()', function () {
    $captured = [];
    Functions\expect('register_post_type')
        ->once()
        ->andReturnUsing(function ($slug, $args) use (&$captured) {
            $captured['pt'] = ['slug' => $slug, 'args' => $args];

            return null;
        });
    Functions\expect('register_taxonomy')
        ->once()
        ->andReturnUsing(function ($tax, $object, $args) use (&$captured) {
            $captured['tax'] = ['tax' => $tax, 'object' => $object, 'args' => $args];

            return null;
        });
    Functions\when('register_post_meta')->justReturn(true);

    (new ClientPostType())->register();

    expect($captured['pt']['slug'])->toBe(ClientPostType::POST_TYPE)
        ->and($captured['tax']['tax'])->toBe(ClientPostType::TAXONOMY)
        ->and($captured['tax']['object'])->toBe(ClientPostType::POST_TYPE);
});

it('registers the statistic, behaviour, play badge, link set, and gallery meta with REST, sanitization, and auth', function () {
    $meta = (new ClientPostType())->metaArgs();

    expect(array_keys($meta))->toBe([
        ClientPostType::META_STAT,
        ClientPostType::META_BEHAVIOR,
        ClientPostType::META_HIDE_PLAY_ICON,
        ClientPostType::META_LINK_URL,
        ClientPostType::META_LINK_KIND,
        ClientPostType::META_LINK_POST_TYPE,
        ClientPostType::META_LINK_POST_ID,
        ClientPostType::META_LINK_NEW_TAB,
        ClientPostType::META_GALLERY,
    ]);
    expect($meta[ClientPostType::META_STAT]['sanitize_callback'])->toBe([ClientPostType::class, 'sanitizeStat'])
        ->and($meta[ClientPostType::META_BEHAVIOR]['sanitize_callback'])->toBe([ClientPostType::class, 'sanitizeBehavior'])
        ->and($meta[ClientPostType::META_LINK_KIND]['sanitize_callback'])->toBe([ClientPostType::class, 'sanitizeLinkKind'])
        ->and($meta[ClientPostType::META_GALLERY]['sanitize_callback'])->toBe([ClientPostType::class, 'sanitizeGallery']);

    foreach ($meta as $args) {
        expect($args['show_in_rest'])->not->toBeFalse()
            ->and($args['single'])->toBeTrue()
            ->and($args['auth_callback'])->toBe([ClientPostType::class, 'authEdit']);
    }
});

/*
 * WordPress writes a `false` boolean to the meta table as `''`, which reads back identically to
 * "never set". A positively-named `show_play_icon` defaulting to true could therefore never be
 * switched off — the off value and the absent value are the same bytes. Storing the negative keeps
 * the only value worth writing the one the editor explicitly chose.
 */
it('stores the play badge inverted, so its default is the falsy value', function () {
    $meta = (new ClientPostType())->metaArgs();

    expect(ClientPostType::META_HIDE_PLAY_ICON)->toBe('_perego_client_hide_play_icon')
        ->and($meta[ClientPostType::META_HIDE_PLAY_ICON]['default'])->toBeFalse();
});

/*
 * Regression, caught by this migration's first dry run (2026-07-28): it reported all 54 live clients
 * as "already migrated" because `META_BEHAVIOR` declared `'default' => 'none'`, and since WP 5.5 a
 * registered default is returned by `get_post_meta()` for a key that was never written. The same
 * masking stops `Content\TranslatedMeta` falling back to the linked English record, which would have
 * rendered every Arabic client card inert — the exact defect that class exists to prevent.
 *
 * A truthy default on any of these three is therefore a bug, not a style choice.
 */
it('declares no truthy meta default that would mask an unwritten key', function () {
    $meta = (new ClientPostType())->metaArgs();

    foreach ([
        ClientPostType::META_BEHAVIOR,
        ClientPostType::META_LINK_KIND,
        ClientPostType::META_LINK_POST_ID,
    ] as $key) {
        expect($meta[$key])->not->toHaveKey('default');
    }
});

it('suppresses the default client-type box so the panel owns that choice', function () {
    expect((new ClientPostType())->taxonomyArgs()['meta_box_cb'])->toBeFalse();
});

it('sanitizes the client statistic through a strong-only kses allowlist', function () {
    $captured = null;
    Functions\when('wp_kses')->alias(function (string $raw, array $allowed) use (&$captured) {
        $captured = $allowed;

        return $raw;
    });
    Functions\when('wp_strip_all_tags')->alias(fn (string $v) => strip_tags($v));

    // The allowlist is what keeps the handoff's bold "+1M" while blocking every other tag; assert its
    // shape rather than re-testing WordPress's own kses implementation.
    expect(ClientPostType::sanitizeStat('<strong>+1M</strong> views'))->toBe('<strong>+1M</strong> views')
        ->and($captured)->toBe(['strong' => []]);
});

/*
 * The subtitle is capped so a long one cannot grow the card and push the whole carousel row taller.
 * The editor panel stops accepting characters at the limit; this is the backstop for REST, WP-CLI,
 * imports and seeders, which have no such control.
 */
describe('subtitle length cap', function () {
    beforeEach(function () {
        Functions\when('wp_kses')->returnArg();
        Functions\when('wp_strip_all_tags')->alias(fn (string $v) => strip_tags($v));
    });

    it('leaves a subtitle within the limit exactly as written', function () {
        expect(ClientPostType::sanitizeStat('<strong>+1M</strong> views'))->toBe('<strong>+1M</strong> views');
    });

    it('counts visible characters only, so markup never spends the budget', function () {
        // 30 visible characters wrapped entirely in <strong> — the raw string is far longer.
        $text = str_repeat('a', ClientPostType::SUBTITLE_MAX_CHARS);

        expect(ClientPostType::sanitizeStat("<strong>{$text}</strong>"))->toBe("<strong>{$text}</strong>");
    });

    it('trims an over-long subtitle to the limit', function () {
        $capped = ClientPostType::sanitizeStat(str_repeat('b', 90));

        expect($capped)->toBe(str_repeat('b', ClientPostType::SUBTITLE_MAX_CHARS));
    });

    // Cutting the raw string could slice a tag in half and leave the renderer's kses to mangle it.
    it('closes a <strong> left open by the cut', function () {
        $capped = ClientPostType::sanitizeStat('<strong>' . str_repeat('c', 90) . '</strong>');

        expect($capped)->toBe('<strong>' . str_repeat('c', ClientPostType::SUBTITLE_MAX_CHARS) . '</strong>')
            ->and(substr_count($capped, '<strong>'))->toBe(substr_count($capped, '</strong>'));
    });

    it('cuts inside the trailing text without disturbing an earlier bold run', function () {
        $capped = ClientPostType::sanitizeStat('<strong>+1M</strong> ' . str_repeat('d', 90));

        expect($capped)->toStartWith('<strong>+1M</strong> ')
            ->and(strip_tags($capped))->toHaveLength(ClientPostType::SUBTITLE_MAX_CHARS);
    });
});

// An unrecognised behaviour must land on the inert card, never on an accidental action.
it('normalizes an unknown behaviour to the inert default', function () {
    expect(ClientPostType::sanitizeBehavior('none'))->toBe('none')
        ->and(ClientPostType::sanitizeBehavior('lightbox'))->toBe('lightbox')
        ->and(ClientPostType::sanitizeBehavior('link'))->toBe('link')
        ->and(ClientPostType::sanitizeBehavior('bogus'))->toBe('none')
        ->and(ClientPostType::sanitizeBehavior(''))->toBe('none');
});

it('normalizes an unknown link kind to a custom URL', function () {
    expect(ClientPostType::sanitizeLinkKind('dynamic'))->toBe('dynamic')
        ->and(ClientPostType::sanitizeLinkKind('custom'))->toBe('custom')
        ->and(ClientPostType::sanitizeLinkKind('bogus'))->toBe('custom');
});

it('reads every shape WordPress may hand back for a boolean meta value', function () {
    expect(ClientPostType::sanitizeBool('1'))->toBeTrue()
        ->and(ClientPostType::sanitizeBool(true))->toBeTrue()
        ->and(ClientPostType::sanitizeBool('true'))->toBeTrue()
        // The three ways "off" reaches us: an explicit false, WordPress's stored empty string, and
        // a key that was never written at all.
        ->and(ClientPostType::sanitizeBool(false))->toBeFalse()
        ->and(ClientPostType::sanitizeBool(''))->toBeFalse()
        ->and(ClientPostType::sanitizeBool(null))->toBeFalse();
});

it('sanitizes a mixed image/video gallery, dropping malformed rows', function () {
    Functions\when('absint')->alias(fn ($v) => abs((int) $v));
    Functions\when('esc_url_raw')->alias(fn ($v) => (string) $v);

    $clean = ClientPostType::sanitizeGallery([
        ['type' => 'image', 'id' => '42'],
        ['type' => 'video', 'url' => 'https://youtube.com/watch?v=abc'],
        // An uploaded video keeps the attachment it came from; a pasted link simply has none.
        ['type' => 'video', 'id' => '7', 'url' => 'https://perego.local/clip.mp4'],
        ['type' => 'image', 'id' => 0], // dropped: no attachment id
        ['type' => 'video', 'url' => ''], // dropped: empty url
        ['type' => 'bogus', 'url' => 'x'], // dropped: unknown type
        'not-an-array', // dropped: malformed row
    ]);

    expect($clean)->toBe([
        ['type' => 'image', 'id' => 42, 'url' => ''],
        ['type' => 'video', 'id' => 0, 'url' => 'https://youtube.com/watch?v=abc'],
        ['type' => 'video', 'id' => 7, 'url' => 'https://perego.local/clip.mp4'],
    ]);
});

it('returns an empty gallery for non-array input', function () {
    expect(ClientPostType::sanitizeGallery('not-an-array'))->toBe([]);
});
