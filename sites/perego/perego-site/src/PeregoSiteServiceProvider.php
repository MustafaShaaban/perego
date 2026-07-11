<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite;

defined('ABSPATH') || exit;

use PeregoSite\Blocks\ExampleRenderer;
use PeregoSite\Controllers\ExampleController;
use PeregoSite\Options\ExampleOptions;
use PeregoSite\Repositories\ExampleRepository;
use PeregoSite\Services\ExampleService;

/**
 * The Perego site service provider — the composition root where your site's pieces are
 * wired. With --starter it registers a runnable example (REST route + block + options page);
 * remove that wiring per REMOVE-EXAMPLE.md and add your own. App code lives under
 * PeregoSite\. REST namespace: perego/v1. Option/CPT prefix: perego_.
 */
final class PeregoSiteServiceProvider
{
    private ExampleService $exampleService;

    public function register(): void
    {
        // Composition root: build the example's object graph once. (--starter example.)
        $this->exampleService = new ExampleService(new ExampleRepository());
    }

    public function boot(): void
    {
        // --starter example wiring — delete this block when you remove the example.
        (new ExampleController($this->exampleService))->register();
        (new ExampleOptions())->register();

        add_action('init', function (): void {
            $renderer = new ExampleRenderer($this->exampleService);
            register_block_type(__DIR__ . '/Blocks/example', [
                'render_callback' => static fn (): string => $renderer->render(),
            ]);
        });
    }
}
