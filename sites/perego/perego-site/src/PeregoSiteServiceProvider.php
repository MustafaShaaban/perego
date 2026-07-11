<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite;

defined('ABSPATH') || exit;

use PeregoSite\Blocks\ExampleRenderer;
use PeregoSite\Blocks\HeroSliderRenderer;
use PeregoSite\Blocks\PortfolioGridRenderer;
use PeregoSite\Blocks\PreloaderRenderer;
use PeregoSite\Blocks\ServiceHeroRenderer;
use PeregoSite\Blocks\ServicesTeaserRenderer;
use PeregoSite\Blocks\SiteFooterRenderer;
use PeregoSite\Blocks\SiteHeaderRenderer;
use PeregoSite\Content\PortfolioContent;
use PeregoSite\Content\ServiceContent;
use PeregoSite\Controllers\ExampleController;
use PeregoSite\Options\ExampleOptions;
use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;
use PeregoSite\Repositories\ExampleRepository;
use PeregoSite\Repositories\ProjectRepository;
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
        $this->registerPortfolio();
        $this->registerServices();
    }

    /**
     * spec 003 (M3, US3): the service CPT (four fixed services at /services/<slug>) and the
     * server-rendered perego-theme/service-hero block (eyebrow + current-service H1 + the shared
     * four-service tabs) used by the single-perego_service template. The editorial "what we do" /
     * "our process" prose lives in each service post's editable block content (post-content), per
     * the editor-canvas rule — see docs/repository-audit.md §6.
     */
    private function registerServices(): void
    {
        add_action('init', function (): void {
            (new ServicePostType())->register();

            $languageService = $this->languageService;
            $heroRenderer = new ServiceHeroRenderer();

            register_block_type($this->blockDir('service-hero'), [
                'render_callback' => static function () use ($heroRenderer, $languageService): string {
                    $content = new ServiceContent($languageService->driver()->currentLocale());
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;

                    // Resolve the canonical service slug from meta, not post_name: a translated
                    // (e.g. Arabic) service post carries a Polylang-de-duplicated slug like
                    // "video-editing-2", but _perego_service_slug always holds the canonical
                    // "video-editing" the ServiceContent map + tab routes are keyed on.
                    $currentSlug = '';
                    if ($queried instanceof \WP_Post) {
                        $meta = get_post_meta($queried->ID, '_perego_service_slug', true);
                        $currentSlug = is_string($meta) && $meta !== '' ? $meta : $queried->post_name;
                    }

                    return $heroRenderer->render($content, $currentSlug);
                },
            ]);
        });
    }

    /**
     * spec 003 (M3): the project CPT + taxonomy, and the perego/portfolio-grid block that queries it.
     */
    private function registerPortfolio(): void
    {
        add_action('init', function (): void {
            (new ProjectPostType())->register();

            $gridRenderer = new PortfolioGridRenderer();
            $languageService = $this->languageService;

            register_block_type($this->blockDir('portfolio-grid'), [
                'render_callback' => static function () use ($gridRenderer, $languageService): string {
                    $content  = new PortfolioContent($languageService->driver()->currentLocale());
                    $projects = (new ProjectRepository())->allForGrid($content);

                    return $gridRenderer->render(
                        $projects,
                        $content->filterLabels(),
                        $content->gridStrings(),
                    );
                },
            ]);
        });
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
