<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Theme;

defined('ABSPATH') || exit;

use WP_HTML_Tag_Processor;

/**
 * Re-attaches the structural HTML attributes a theme template needs but `core/group`'s `save()`
 * cannot generate (spec 021).
 *
 * WHY THIS EXISTS — read this before "fixing" a template by hand-editing its markup back.
 * The block editor validates every block in a template by regenerating the block's `save()` output
 * and comparing the attribute set against what the file actually contains. `core/group` emits only
 * `class` (plus `id` when the `anchor` attribute is set, and `style` when a style attribute is set);
 * it has no way to emit `aria-labelledby`. `front-page.html` used to hard-code
 * `id="about" aria-labelledby="home-about-title"` on its `.home-about` section, so the editor saw
 * two attributes it could not account for and rendered the whole About section as
 * "Block contains unexpected or invalid content" — the defect the owner reported on 2026-07-22.
 *
 * The section stays a real `core/group` rather than becoming a custom wrapper block, because core
 * appends `is-layout-flow wp-block-group-is-layout-flow` and the `theme.json` layout/spacing rules
 * to group blocks at RENDER time; a custom block would silently drop them and change the front end.
 * So the template now carries exactly what `save()` produces, and the missing attributes are put
 * back here, on `render_block`. The rendered section is DOM-identical to what the template used to
 * emit, but not byte-identical: WP_HTML_Tag_Processor writes a restored attribute in front of the
 * tag's existing ones, so `id`/`aria-labelledby` now precede `class` instead of following it.
 * Attribute order carries no meaning in HTML — nothing downstream (CSS, JS, a11y tree) sees a
 * difference — and the trade buys back a template the block editor can validate. Do not chase
 * byte-identity by putting the attributes back in the template: that is the defect.
 *
 * Adding a case: key it on the group's own `className` and cover it in
 * `tests/Theme/TemplateSectionAttributesTest.php`. Prefer a native expression (core's `anchor` for a
 * plain `id`, core's `style.spacing` for margins) whenever core reproduces the string exactly — this
 * class is the fallback for attributes core simply cannot express. See spec 021 T035.
 */
final class TemplateSectionAttributes
{
    /**
     * Attributes to restore, keyed by `<blockName> <className>` — the pair that identifies a section.
     * The block name is part of the key because the same className can appear on different core blocks
     * (and inside a Perego renderer's own output, which this must never touch).
     *
     * @var array<string, array<string, string>>
     */
    private const SECTIONS = [
        // Homepage About section: `#about` is the header nav's in-page anchor target, and
        // `aria-labelledby` names the landmark from the first panel heading (seeded with the
        // matching anchor by scripts/seed-home-about.php).
        'core/group home-about' => [
            'id' => 'about',
            'aria-labelledby' => 'home-about-title',
        ],
        // The journal index + category archive query loop (home.html, archive.html): the designed gap
        // between the archive header and the post grid.
        'core/query journal-archive__inner' => [
            'style' => 'margin-top:clamp(32px,4vw,52px)',
        ],
        // The centred archive/page header block (archive.html, page.html). `text-align` is not a
        // `core/group` support, so core cannot emit it from a block attribute.
        'core/group post-hero__inner' => [
            'style' => 'text-align:center',
        ],
        // The contact hero (page-contact.html): `#contactChoose` is the in-page anchor the service
        // chooser scrolls to. Its decorative background div is raw markup and is wrapped in `wp:html`
        // in the template instead, which core round-trips verbatim.
        'core/group contact-hero' => [
            'id' => 'contactChoose',
        ],
    ];

    /**
     * The attributes a parsed block needs restored — empty for every block this class does not own.
     *
     * @param array<string, mixed> $block a parsed block, as passed to the `render_block` filter
     * @return array<string, string>
     */
    public function attributesFor(array $block): array
    {
        $blockName = $block['blockName'] ?? null;
        $attrs = $block['attrs'] ?? [];
        $className = is_array($attrs) ? ($attrs['className'] ?? null) : null;

        if (! is_string($blockName) || ! is_string($className)) {
            return [];
        }

        return self::SECTIONS[$blockName . ' ' . $className] ?? [];
    }

    /**
     * Restores the attributes on the block's outermost tag, leaving every other block untouched.
     *
     * @param array<string, mixed> $block a parsed block, as passed to the `render_block` filter
     */
    public function apply(string $html, array $block): string
    {
        $attributes = $this->attributesFor($block);

        if ($attributes === [] || $html === '') {
            return $html;
        }

        $tags = new WP_HTML_Tag_Processor($html);

        if (! $tags->next_tag()) {
            return $html;
        }

        foreach ($attributes as $name => $value) {
            $tags->set_attribute($name, $value);
        }

        return $tags->get_updated_html();
    }
}
