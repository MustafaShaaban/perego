<?php

/**
 * Every Perego form must name each listener at most once.
 *
 * CoreX v0.40.0's `FormsServiceProvider::registerListeners()` is a bare `foreach ($form->listeners())`
 * with no de-duplication — our fork used to dedupe and that edit is gone, because carrying it would
 * re-conflict at the next update. A duplicated `StoreSubmissionListener` would therefore store the
 * submission twice and send two notification emails for one enquiry, with nothing to indicate why.
 *
 * The risk is latent today (both forms name one listener) and this keeps it that way. Reported upstream.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

it('declares no duplicate listener ids on any form', function () {
    $forms = glob(dirname(__DIR__, 2) . '/src/Forms/*Form.php') ?: [];

    expect($forms)->not->toBeEmpty();

    $checked = 0;

    foreach ($forms as $file) {
        $source = (string) file_get_contents($file);

        if (! preg_match('/function listeners\(\).*?return \[(.*?)\];/s', $source, $matches)) {
            continue;
        }

        // Listener ids are written unqualified here (`StoreSubmissionListener::class`), so a bare word
        // match is enough — and avoids escaping a namespace separator inside a character class, which
        // is what silently broke the first version of this pattern.
        preg_match_all('/(\w+)::class/', $matches[1], $ids);
        $listeners = $ids[1] ?? [];
        $checked++;

        expect($listeners)->toBe(
            array_values(array_unique($listeners)),
            basename($file) . ' names the same listener twice; upstream v0.40.0 would run it twice.'
        );
    }

    // Without this the test passes vacuously the day someone renames listeners().
    expect($checked)->toBeGreaterThan(0);
});
