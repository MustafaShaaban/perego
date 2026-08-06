<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

it('keeps static FSE wave images aligned with the CoreX picture contract', function () {
    $templates = dirname(__DIR__, 3) . '/perego-theme/templates';

    foreach (['front-page.html', 'home.html', 'single.html'] as $template) {
        $html = file_get_contents($templates . '/' . $template);

        expect($html)->not->toBeFalse()
            ->and((string) $html)->toContain('<source type="image/webp"')
            ->toContain('/assets/images/wave-ribbon.webp')
            ->toContain('/assets/images/wave-ribbon.png')
            ->toContain('width="2560" height="1376"')
            ->toContain('loading="lazy" decoding="async"');
    }
});
