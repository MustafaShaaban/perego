<?php

/**
 * Seeds the homepage hero content (three slide title/text pairs + CTA) onto each language's front
 * page from the HomeContent handoff seed, so the hero renders from editable post meta with the exact
 * current copy pre-filled. Keyed by the front page and its Polylang translations, each seeded from
 * its own locale. Idempotent (re-running writes the same values). Non-destructive: only the seven
 * hero meta keys are written, never owner content.
 *
 * Dry-run by default; SEED_HERO_MODE=apply to write. Run:
 *   SEED_HERO_MODE=dryrun wp eval 'require "sites/perego/perego-site/scripts/seed-hero-content.php";' --user=admin
 *   SEED_HERO_MODE=apply  wp eval 'require "sites/perego/perego-site/scripts/seed-hero-content.php";' --user=admin
 *
 * @package PeregoSite
 */

use PeregoSite\Content\HeroContent;
use PeregoSite\Content\HomeContent;

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Must run inside WordPress (wp eval).\n");

    return;
}

$mode  = getenv('SEED_HERO_MODE') ?: 'dryrun';
$apply = $mode === 'apply';

$frontId = (int) get_option('page_on_front');
if ($frontId <= 0) {
    echo "No static front page configured (page_on_front). Nothing to seed.\n";

    return;
}

// The front page and each of its Polylang translations, mapped to their locale.
$targets = [];
if (function_exists('pll_get_post_translations')) {
    foreach (pll_get_post_translations($frontId) as $locale => $postId) {
        $targets[(int) $postId] = (string) $locale;
    }
}
if ($targets === []) {
    $targets[$frontId] = 'en';
}

$lines   = [];
$written = 0;

foreach ($targets as $postId => $locale) {
    $home   = new HomeContent($locale);
    $slides = $home->heroSlides();

    $pairs = [];
    foreach ($slides as $index => $slide) {
        $pairs[HeroContent::META_SLIDE_TITLE[$index] ?? "extra_title_{$index}"] = $slide['title'];
        $pairs[HeroContent::META_SLIDE_TEXT[$index] ?? "extra_text_{$index}"]   = $slide['text'];
    }
    $pairs[HeroContent::META_CTA] = $home->heroCta();

    foreach ($pairs as $key => $value) {
        $current = (string) get_post_meta($postId, $key, true);
        $needs   = $current !== $value;
        $lines[] = sprintf('page=%d %s %s%s', $postId, $locale, $key, $needs ? ' [SET]' : ' [ok]');

        if ($apply && $needs) {
            update_post_meta($postId, $key, $value);
            $written++;
        }
    }
}

echo "SEED-HERO MODE={$mode}\n";
echo implode("\n", $lines) . "\n";
echo $apply ? "Applied {$written} meta writes.\n" : "Dry-run only — no writes. Set SEED_HERO_MODE=apply to write.\n";
