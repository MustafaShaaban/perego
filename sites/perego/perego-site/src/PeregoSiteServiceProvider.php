<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite;

defined('ABSPATH') || exit;

use PeregoSite\Blocks\ExampleRenderer;
use PeregoSite\Blocks\HeroSliderRenderer;
use PeregoSite\Blocks\PreloaderRenderer;
use PeregoSite\Blocks\ServicesTeaserRenderer;
use PeregoSite\Blocks\SiteFooterRenderer;
use PeregoSite\Blocks\SiteHeaderRenderer;
use PeregoSite\Controllers\ExampleController;
use PeregoSite\Options\ExampleOptions;
use PeregoSite\Repositories\ExampleRepository;
use PeregoSite\Services\ExampleService;
use PeregoSite\Services\LanguageService;

/**
 * The Perego site service provider — the composition root where your site's pieces are
 * wired. With --starter it registers a runnable example (REST route + block + options page);
 * remove that wiring per REMOVE-EXAMPLE.md and add your own. App code lives under
 * PeregoSite\. REST namespace: perego/v1. Option/CPT prefix: perego_.
 */
final class PeregoSiteServiceProvider
{
    private ExampleService $exampleService;

    private LanguageService $languageService;

    public function register(): void
    {
        // Composition root: build the example's object graph once. (--starter example.)
        $this->exampleService = new ExampleService(new ExampleRepository());

        // spec 001: language mechanism, built from the current request so blocks render the
        // visitor's actual stored choice on first paint (no flash-of-wrong-language).
        $this->languageService = new LanguageService(
            cookie: $_COOKIE,
            requestUri: $_SERVER['REQUEST_URI'] ?? '/',
        );
    }

    public function boot(): void
    {
        // --starter example wiring — delete this block when you remove the example.
        (new ExampleController($this->exampleService))->register();
        (new ExampleOptions())->register();

        add_action('init', function (): void {
            $renderer = new ExampleRenderer($this->exampleService);
            register_block_type($this->blockDir('example'), [
                'render_callback' => static fn (): string => $renderer->render(),
            ]);
        });

        $this->registerGlobalShellBlocks();
        $this->registerHomeBlocks();
    }

    /**
     * spec 001: the header/footer that every page template shares.
     */
    private function registerGlobalShellBlocks(): void
    {
        add_action('init', function (): void {
            $headerRenderer = new SiteHeaderRenderer($this->languageService);
            register_block_type($this->blockDir('site-header'), [
                'render_callback' => static function () use ($headerRenderer): string {
                    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

                    return $headerRenderer->render($path !== '' ? $path : '/');
                },
            ]);

            $footerRenderer = new SiteFooterRenderer();
            register_block_type($this->blockDir('site-footer'), [
                'render_callback' => static function (array $attributes) use ($footerRenderer): string {
                    return $footerRenderer->render((bool) ($attributes['flat'] ?? false));
                },
            ]);

            $preloaderRenderer = new PreloaderRenderer();
            register_block_type($this->blockDir('preloader'), [
                'render_callback' => static fn (): string => $preloaderRenderer->render(),
            ]);
        });
    }

    /**
     * spec 002 (M2): the homepage hero slider + services teaser.
     */
    private function registerHomeBlocks(): void
    {
        add_action('init', function (): void {
            $heroRenderer = new HeroSliderRenderer($this->languageService);
            register_block_type($this->blockDir('hero-slider'), [
                'render_callback' => static fn (): string => $heroRenderer->render(),
            ]);

            $teaserRenderer = new ServicesTeaserRenderer($this->languageService);
            register_block_type($this->blockDir('services-teaser'), [
                'render_callback' => static fn (): string => $teaserRenderer->render(),
            ]);
        });
    }

    /**
     * Resolve a block's registration directory: the compiled `build/Blocks/<name>` (with its
     * style.css + bundled view.js + generated .asset.php) when a build has run, else the
     * `src/Blocks/<name>` source so registration still works pre-build (markup renders; the raw
     * SCSS/ESM assets just won't load until `npm run build`). Build output is gitignored and
     * regenerated — see DECISIONS.md.
     */
    private function blockDir(string $name): string
    {
        $built = dirname(__DIR__) . '/build/Blocks/' . $name;

        return is_dir($built) ? $built : __DIR__ . '/Blocks/' . $name;
    }

    /**
     * Exposed so block render callbacks (site-header, site-footer) can reach the language
     * mechanism without reaching into the framework's own container — this is site-level
     * composition, per the --starter example's own established pattern.
     */
    public function languageService(): LanguageService
    {
        return $this->languageService;
    }
}
