<?php

/**
 * Seed the perego_global_section records (spec Phase 5) — the editor-managed, Polylang-linked home
 * for the site's repeated global copy. Idempotent: for each role it creates the EN + AR record only
 * when that role+language does not already exist, then links the pair as Polylang translations. It
 * NEVER overwrites later editor changes (existing records are left untouched), and fails clearly when
 * Polylang is inactive (records are still created, just unlinked, with a warning).
 *
 * Copy is the approved handoff content (content/{en,ar}.json) — no invention. Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-global-sections.php";' --path=wp
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval-file.\n");
    exit(1);
}

use PeregoSite\PostTypes\GlobalSectionPostType;

if (! post_type_exists(GlobalSectionPostType::POST_TYPE)) {
    WP_CLI::error('perego_global_section is not registered — is perego-site active?');
}

/**
 * role => [ 'en' => [title, blocks], 'ar' => [title, blocks] ].
 * Block content is core-block markup so every sentence stays editable on the canvas.
 */
$sections = [
    'header' => [
        'en' => ['title' => 'Header content (EN)', 'blocks' => '<!-- wp:paragraph --><p>Creative Studio</p><!-- /wp:paragraph -->'],
        'ar' => ['title' => 'محتوى الهيدر (AR)', 'blocks' => '<!-- wp:paragraph --><p>استوديو إبداعي</p><!-- /wp:paragraph -->'],
    ],
    'standard-footer' => [
        'en' => ['title' => 'Standard footer (EN)', 'blocks' => "<!-- wp:heading {\"level\":2} --><h2>Contact us</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We would be delighted to hear from you to provide creative technical solutions, assistance, and tailored recommendations that best suit your needs.</p><!-- /wp:paragraph -->"],
        'ar' => ['title' => 'الفوتر القياسي (AR)', 'blocks' => "<!-- wp:heading {\"level\":2} --><h2>تواصل معنا</h2><!-- /wp:heading --><!-- wp:paragraph --><p>يسعدنا أن نسمع منك لنقدّم حلولًا تقنية إبداعية، ومساعدة، وتوصيات مخصصة تناسب احتياجاتك.</p><!-- /wp:paragraph -->"],
    ],
    'contact-footer' => [
        'en' => ['title' => 'Contact (flat) footer (EN)', 'blocks' => "<!-- wp:heading {\"level\":2} --><h2>Contact us</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We would be delighted to hear from you to provide creative technical solutions, assistance, and tailored recommendations that best suit your needs.</p><!-- /wp:paragraph -->"],
        'ar' => ['title' => 'فوتر التواصل المسطّح (AR)', 'blocks' => "<!-- wp:heading {\"level\":2} --><h2>تواصل معنا</h2><!-- /wp:heading --><!-- wp:paragraph --><p>يسعدنا أن نسمع منك لنقدّم حلولًا تقنية إبداعية، ومساعدة، وتوصيات مخصصة تناسب احتياجاتك.</p><!-- /wp:paragraph -->"],
    ],
    'footer-careers' => [
        'en' => ['title' => 'Footer careers (EN)', 'blocks' => "<!-- wp:heading {\"level\":2,\"className\":\"footer-heading\"} --><h2 class=\"wp-block-heading footer-heading\">Join us</h2><!-- /wp:heading --><!-- wp:paragraph {\"className\":\"footer-blurb\"} --><p class=\"footer-blurb\">We're constantly evolving. If you're ready to grow with us, share your CV and portfolio.</p><!-- /wp:paragraph -->"],
        'ar' => ['title' => 'الانضمام إلينا في الفوتر (AR)', 'blocks' => "<!-- wp:heading {\"level\":2,\"className\":\"footer-heading\"} --><h2 class=\"wp-block-heading footer-heading\">انضم إلينا</h2><!-- /wp:heading --><!-- wp:paragraph {\"className\":\"footer-blurb\"} --><p class=\"footer-blurb\">نحن في تطوّر مستمر. إن كنت مستعدًا للنمو معنا، شاركنا سيرتك الذاتية وأعمالك.</p><!-- /wp:paragraph -->"],
    ],
    'global-cta' => [
        'en' => ['title' => 'Global project CTA (EN)', 'blocks' => "<!-- wp:heading {\"level\":2} --><h2>Start a Project</h2><!-- /wp:heading --><!-- wp:paragraph --><p>We would be delighted to hear from you to provide creative technical solutions, assistance, and tailored recommendations that best suit your needs.</p><!-- /wp:paragraph -->"],
        'ar' => ['title' => 'دعوة بدء المشروع (AR)', 'blocks' => "<!-- wp:heading {\"level\":2} --><h2>ابدأ مشروعك</h2><!-- /wp:heading --><!-- wp:paragraph --><p>يسعدنا أن نسمع منك لنقدّم حلولًا تقنية إبداعية، ومساعدة، وتوصيات مخصصة تناسب احتياجاتك.</p><!-- /wp:paragraph -->"],
    ],
    'contact-details' => [
        'en' => ['title' => 'Global contact details (EN)', 'blocks' => "<!-- wp:list --><ul><!-- wp:list-item --><li>mostafa.emam3313@gmail.com</li><!-- /wp:list-item --><!-- wp:list-item --><li>yehemam2@gmail.com</li><!-- /wp:list-item --><!-- wp:list-item --><li>+996 56 293 2759</li><!-- /wp:list-item --><!-- wp:list-item --><li>+20 111 54 855 72</li><!-- /wp:list-item --></ul><!-- /wp:list -->"],
        'ar' => ['title' => 'بيانات التواصل العامة (AR)', 'blocks' => "<!-- wp:list --><ul><!-- wp:list-item --><li>mostafa.emam3313@gmail.com</li><!-- /wp:list-item --><!-- wp:list-item --><li>yehemam2@gmail.com</li><!-- /wp:list-item --><!-- wp:list-item --><li>+996 56 293 2759</li><!-- /wp:list-item --><!-- wp:list-item --><li>+20 111 54 855 72</li><!-- /wp:list-item --></ul><!-- /wp:list -->"],
    ],
    'not-found' => [
        'en' => ['title' => '404 editorial (EN)', 'blocks' => "<!-- wp:heading {\"level\":2} --><h2>This page took a creative detour</h2><!-- /wp:heading --><!-- wp:paragraph --><p>The page you're looking for doesn't exist or has been moved. Let's get you back to something worth watching.</p><!-- /wp:paragraph -->"],
        'ar' => ['title' => 'محتوى صفحة 404 (AR)', 'blocks' => "<!-- wp:heading {\"level\":2} --><h2>هذه الصفحة سلكت منعطفًا إبداعيًا</h2><!-- /wp:heading --><!-- wp:paragraph --><p>الصفحة التي تبحث عنها غير موجودة أو تم نقلها. لنعُد بك إلى ما يستحق المشاهدة.</p><!-- /wp:paragraph -->"],
    ],
];

$pllActive = function_exists('pll_set_post_language') && function_exists('pll_save_post_translations');
if (! $pllActive) {
    WP_CLI::warning('Polylang is inactive — records will be created but not language-linked.');
}

$created = 0;
$skipped = 0;

foreach ($sections as $role => $langs) {
    $ids = [];

    foreach ($langs as $locale => $data) {
        $existing = seed_gs_find($role, $locale, $pllActive);
        if ($existing > 0) {
            $ids[$locale] = $existing;
            ++$skipped;
            continue;
        }

        $id = wp_insert_post([
            'post_type' => GlobalSectionPostType::POST_TYPE,
            'post_status' => 'publish',
            'post_title' => $data['title'],
            'post_content' => $data['blocks'],
            'meta_input' => [GlobalSectionPostType::META_ROLE => $role],
        ], true);

        if (is_wp_error($id)) {
            WP_CLI::warning(sprintf('Failed to create %s/%s: %s', $role, $locale, $id->get_error_message()));
            continue;
        }

        $ids[$locale] = (int) $id;
        ++$created;

        if ($pllActive) {
            pll_set_post_language((int) $id, $locale);
        }
    }

    // Link the EN/AR pair as translations (idempotent — Polylang merges the group).
    if ($pllActive && isset($ids['en'], $ids['ar'])) {
        pll_save_post_translations(['en' => $ids['en'], 'ar' => $ids['ar']]);
    }
}

WP_CLI::success(sprintf('Global sections seeded: %d created, %d already present.', $created, $skipped));

/**
 * Find an existing published record for a role in a given language, or 0. When Polylang is active the
 * language filter is authoritative; otherwise role alone identifies it.
 */
function seed_gs_find(string $role, string $locale, bool $pllActive): int
{
    $query = [
        'post_type' => GlobalSectionPostType::POST_TYPE,
        'post_status' => 'any',
        'numberposts' => 20,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_key' => GlobalSectionPostType::META_ROLE,
        'meta_value' => $role,
    ];
    if ($pllActive) {
        $query['lang'] = $locale;
    }

    $found = get_posts($query);
    if ($found === []) {
        return 0;
    }

    if (! $pllActive) {
        return (int) $found[0];
    }

    foreach ($found as $id) {
        if (pll_get_post_language((int) $id) === $locale) {
            return (int) $id;
        }
    }

    return 0;
}
