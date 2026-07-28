<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Forms;

defined('ABSPATH') || exit;

/**
 * Appends a translated "(optional)" marker to every non-required field label in a Corex form
 * (client request 2026-07-26).
 *
 * WHY THIS LIVES HERE AND NOT IN corex-forms — read before "fixing" it upstream.
 * `Corex\Forms\Block\FieldRenderer` emits `<span class="corex-form__required">*</span>` for required
 * fields and nothing for optional ones, and it exposes no filter for the label. It is framework code,
 * which this site must not edit (Role Gate: Client Site Mode). So the marker is added from the client
 * side, on core's `render_block` hook — the same hook {@see \PeregoSite\Theme\TemplateSectionAttributes}
 * already uses to put attributes back on template sections.
 *
 * It is done on the SERVER, not in CSS. A `::after { content: "(optional)" }` would not be translatable
 * and would print English on the Arabic form; the constitution requires user-facing strings to go
 * through the translation catalogue.
 *
 * A regex is the right tool here rather than `WP_HTML_Tag_Processor`: that processor can read tags and
 * rewrite attributes but cannot insert a child node, which is exactly what this needs. The input is not
 * arbitrary HTML — it is one known renderer's output — and the pattern is pinned to its exact class
 * contract, so a shape it does not recognise is left untouched rather than mangled. Covered by
 * `tests/Forms/OptionalFieldMarkerTest.php`.
 */
final class OptionalFieldMarker
{
    /** The block whose output carries these labels. */
    public const BLOCK_NAME = 'corex/form';

    /**
     * Matches one visible field label and captures its inner HTML. `[^>]*` on the tag stays inside the
     * open tag, and the lazy body stops at the first `</label>` — labels never nest.
     */
    private const LABEL_PATTERN = '#(<label\b[^>]*\bclass="[^"]*corex-form__label--visible[^"]*"[^>]*>)(.*?)(</label>)#s';

    /**
     * @param string $html the block's rendered HTML
     */
    public function mark(string $html): string
    {
        if (! str_contains($html, 'corex-form__label--visible')) {
            return $html;
        }

        $marker = ' <span class="corex-form__optional">'
            . esc_html__('(optional)', 'perego-site')
            . '</span>';

        $result = preg_replace_callback(
            self::LABEL_PATTERN,
            static function (array $match) use ($marker): string {
                // A required field already carries its own marker; an already-processed label must not
                // collect a second one (render_block can run more than once for a cached block).
                if (str_contains($match[2], 'corex-form__required')
                    || str_contains($match[2], 'corex-form__optional')) {
                    return $match[0];
                }

                return $match[1] . $match[2] . $marker . $match[3];
            },
            $html,
        );

        // preg_replace_callback returns null only on a backtrack/recursion limit. Keep the original
        // markup in that case rather than emptying the form.
        return is_string($result) ? $result : $html;
    }
}
