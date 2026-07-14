<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite;

defined('ABSPATH') || exit;

use PeregoSite\Blocks\HeroSliderRenderer;
use PeregoSite\Blocks\HomeAboutBgRenderer;
use PeregoSite\Blocks\PortfolioGridRenderer;
use PeregoSite\Blocks\PostBreadcrumbRenderer;
use PeregoSite\Blocks\PostReadingTimeRenderer;
use PeregoSite\Blocks\ClientsCarouselRenderer;
use PeregoSite\Blocks\ContactServiceChooserRenderer;
use PeregoSite\Blocks\JournalHeaderRenderer;
use PeregoSite\Blocks\LegalTocRenderer;
use PeregoSite\Blocks\MediaLightboxRenderer;
use PeregoSite\Blocks\NotFoundRenderer;
use PeregoSite\Blocks\PreloaderRenderer;
use PeregoSite\Blocks\ProjectHeroRenderer;
use PeregoSite\Blocks\ProjectGalleryLightboxRenderer;
use PeregoSite\Blocks\ProjectNavigationRenderer;
use PeregoSite\Blocks\SearchResultsRenderer;
use PeregoSite\Blocks\ServiceHeroRenderer;
use PeregoSite\Blocks\ServiceSelectedWorkRenderer;
use PeregoSite\Blocks\ServicesOverviewRenderer;
use PeregoSite\Blocks\ServicesTeaserRenderer;
use PeregoSite\Blocks\SiteFooterRenderer;
use PeregoSite\Blocks\SiteHeaderRenderer;
use PeregoSite\Blocks\GlobalSectionRenderer;
use PeregoSite\Content\ClientsContent;
use PeregoSite\Content\GlobalContent;
use PeregoSite\Content\GlobalSectionResolver;
use PeregoSite\Content\PortfolioContent;
use PeregoSite\Content\ServiceContent;
use PeregoSite\PostTypes\ClientPostType;
use PeregoSite\PostTypes\GlobalSectionPostType;
use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;
use PeregoSite\Repositories\ProjectRepository;
use PeregoSite\Seo\StructuredData;
use PeregoSite\Services\LanguageService;

/**
 * The Perego site service provider — the composition root where the site's pieces are wired:
 * the language mechanism, the global shell (header/footer/preloader), home, portfolio, services,
 * global sections + surfaces, clients, forms, SEO/structured data, and agent readiness. App code
 * lives under PeregoSite\. REST namespace: perego/v1. Option/CPT prefix: perego_.
 */
final class PeregoSiteServiceProvider
{
    private LanguageService $languageService;

    public function register(): void
    {
        // spec 001: language mechanism, built from the current request so blocks render the
        // visitor's actual stored choice on first paint (no flash-of-wrong-language).
        $this->languageService = new LanguageService(
            cookie: $_COOKIE,
            requestUri: $_SERVER['REQUEST_URI'] ?? '/',
        );
    }

    public function boot(): void
    {
        $this->registerGlobalShellBlocks();
        $this->registerHomeBlocks();
        $this->registerPortfolio();
        $this->registerServices();
        $this->registerTranslatablePostTypes();
        $this->registerGlobalSurfaces();
        $this->registerGlobalSections();
        $this->registerClients();
        $this->registerContactServiceChooser();
        $this->registerForms();

        (new StructuredData())->register();
        (new \PeregoSite\Seo\PeregoAgentReadiness())->register();
        (new \PeregoSite\Seo\PeregoMeta($this->languageService->driver()->currentLocale()))->register();
        (new \PeregoSite\Seo\PlaceholderPageIndexing())->register();
    }

    /**
     * spec Phase 7: register Perego's site forms into the shared CoreX Forms registry. This is
     * client-site composition — resolving the framework's already-bound FormRegistry singleton
     * through the public application accessor, not editing framework code. Runs on `init` (after
     * the framework has booted on plugins_loaded) and only when CoreX Forms is active, so the site
     * degrades gracefully when the plugin is absent. The engine's default submission listeners
     * (store + email) are shared across forms, so registering here — after boot — still delivers.
     */
    private function registerForms(): void
    {
        add_action('init', static function (): void {
            if (! class_exists(\Corex\Boot::class) || ! class_exists(\Corex\Forms\FormRegistry::class)) {
                return;
            }

            $container = \Corex\Boot::app()->container();
            if (! $container->has(\Corex\Forms\FormRegistry::class)) {
                return;
            }

            $registry = $container->make(\Corex\Forms\FormRegistry::class);
            $registry->register(new \PeregoSite\Forms\QuickMessageForm());
            $registry->register(new \PeregoSite\Forms\ProjectBriefForm());

            self::registerFormEmail($container);
        }, 20);
    }

    /**
     * spec Phase 7/8: wire the branded submitter-confirmation emails. When the CoreX Mail engine is
     * active, register a listener on the shared FormSubmittedEvent that renders the exact handoff
     * email (PeregoEmailRenderer) and sends it through the CoreX Mailer (PeregoMailer). The submitter
     * is not a recipient of the engine's own SendEmailListener (which notifies the admin inbox), so
     * this adds the user-facing confirmation without duplicating the internal notification.
     */
    private static function registerFormEmail(\Corex\Container\ContainerInterface $container): void
    {
        if (! $container->has(\Corex\Mail\Mailer::class) || ! $container->has(\Corex\Events\ListenerProvider::class)) {
            return;
        }

        $logoUrl = function_exists('plugins_url')
            ? plugins_url('assets/email/logo-full.png', dirname(__DIR__) . '/perego-site.php')
            : '';
        $siteUrl = function_exists('home_url') ? (string) home_url('/') : 'https://perego.local';

        $mailer = new \PeregoSite\Email\PeregoMailer(
            $container->make(\Corex\Mail\Mailer::class),
            new \PeregoSite\Email\PeregoEmailRenderer(rtrim($siteUrl, '/'), $logoUrl),
        );

        $container->make(\Corex\Events\ListenerProvider::class)->listen(
            \Corex\Forms\Submission\FormSubmittedEvent::class,
            new \PeregoSite\Email\PeregoFormMailListener($mailer),
        );

        // Branded comment-moderation email (replaces WordPress's plain native notifications).
        (new \PeregoSite\Email\PeregoCommentNotifier($mailer))->register();

        // The "Join us" / CV submission endpoint (secure upload → store → branded emails).
        add_action('rest_api_init', static function () use ($mailer): void {
            (new \PeregoSite\Careers\PeregoCareersController($mailer))->register();
        });
    }

    /**
     * spec Phase 5/10: declare every Perego custom post type translatable through Polylang's public
     * `pll_get_post_types` filter (a Free-edition API — no Pro dependency), so linked EN/AR records
     * resolve by language AND their `/ar/…` single + archive URLs route correctly (verified defect:
     * without this the AR service/work archives 404). The filter is inert when Polylang is inactive.
     */
    private function registerTranslatablePostTypes(): void
    {
        $postTypes = [
            GlobalSectionPostType::POST_TYPE,
            ServicePostType::POST_TYPE,
            ProjectPostType::POST_TYPE,
            ClientPostType::POST_TYPE,
        ];

        add_filter('pll_get_post_types', static function ($types) use ($postTypes) {
            if (is_array($types)) {
                foreach ($postTypes as $postType) {
                    $types[$postType] = $postType;
                }
            }

            return $types;
        }, 10, 1);
    }

    /**
     * spec Phase 5: the editor-managed, Polylang-translatable perego_global_section CPT and the
     * perego-theme/global-section block that renders a role's current-language record inside the
     * language-neutral FSE template parts.
     */
    private function registerGlobalSections(): void
    {
        add_action('init', function (): void {
            (new GlobalSectionPostType())->register();

            $languageService = $this->languageService;

            register_block_type($this->blockDir('global-section'), [
                'render_callback' => static function (array $attributes) use ($languageService): string {
                    return (new GlobalSectionRenderer(
                        $languageService->driver()->currentLocale(),
                        new GlobalSectionResolver(),
                    ))->render($attributes);
                },
            ]);
        });
    }

    /**
     * spec M6: the client CPT (+ type taxonomy) and the perego-theme/clients-carousel block that
     * renders the homepage Corporate + Individual carousels (server-rendered cards + modular Swiper).
     */
    private function registerClients(): void
    {
        add_action('init', function (): void {
            (new ClientPostType())->register();

            $languageService = $this->languageService;

            register_block_type($this->blockDir('clients-carousel'), [
                'render_callback' => static function () use ($languageService): string {
                    $locale = $languageService->driver()->currentLocale();

                    return (new ClientsCarouselRenderer(new ClientsContent($locale), $locale))->render();
                },
            ]);
        });
    }

    /**
     * spec 004 (M4): language-aware blocks for the language-neutral FSE templates that carry editorial
     * copy — currently the 404 composition. GlobalContent resolves EN/AR at render time.
     */
    private function registerGlobalSurfaces(): void
    {
        add_action('init', function (): void {
            $languageService = $this->languageService;

            register_block_type($this->blockDir('not-found'), [
                'render_callback' => static function () use ($languageService): string {
                    return (new NotFoundRenderer(
                        new GlobalContent($languageService->driver()->currentLocale())
                    ))->render();
                },
            ]);

            register_block_type($this->blockDir('search-results'), [
                'render_callback' => static function () use ($languageService): string {
                    return (new SearchResultsRenderer(
                        new GlobalContent($languageService->driver()->currentLocale())
                    ))->render();
                },
            ]);

            register_block_type($this->blockDir('legal-toc'), [
                'render_callback' => static function () use ($languageService): string {
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;

                    return (new LegalTocRenderer(
                        new GlobalContent($languageService->driver()->currentLocale())
                    ))->render($queried instanceof \WP_Post ? $queried : null);
                },
            ]);

            register_block_type($this->blockDir('journal-header'), [
                'render_callback' => static function () use ($languageService): string {
                    return (new JournalHeaderRenderer(
                        new GlobalContent($languageService->driver()->currentLocale())
                    ))->render();
                },
            ]);

            register_block_type($this->blockDir('post-breadcrumb'), [
                'render_callback' => static function () use ($languageService): string {
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;

                    return (new PostBreadcrumbRenderer(
                        new GlobalContent($languageService->driver()->currentLocale())
                    ))->render($queried instanceof \WP_Post ? $queried : null);
                },
            ]);

            register_block_type($this->blockDir('post-reading-time'), [
                'render_callback' => static function () use ($languageService): string {
                    // get_queried_object() is only correct on a singular page; inside a Query Loop
                    // (e.g. the journal archive's post-template) it still points at the archive
                    // itself, not the post currently being rendered. get_post() correctly resolves
                    // the loop's current global $post in both contexts.
                    $current = function_exists('get_post') ? get_post() : null;

                    return (new PostReadingTimeRenderer(
                        new GlobalContent($languageService->driver()->currentLocale())
                    ))->render($current instanceof \WP_Post ? $current : null);
                },
            ]);

            register_block_type($this->blockDir('join-form'), [
                'render_callback' => static function () use ($languageService): string {
                    return (new \PeregoSite\Blocks\JoinFormRenderer(
                        new GlobalContent($languageService->driver()->currentLocale())
                    ))->render();
                },
            ]);
        });
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
            $overviewRenderer = new ServicesOverviewRenderer();

            register_block_type($this->blockDir('services-overview'), [
                'render_callback' => static function () use ($overviewRenderer, $languageService): string {
                    $projects = new ProjectRepository();
                    $portfolioContent = new PortfolioContent($languageService->driver()->currentLocale());

                    $posts = (new \WP_Query([
                        'post_type' => ProjectPostType::POST_TYPE,
                        'post_status' => 'publish',
                        'posts_per_page' => 9,
                        'no_found_rows' => true,
                        'orderby' => 'date',
                        'order' => 'DESC',
                    ]))->posts;

                    $selectedWork = array_map(static function (\WP_Post $post) use ($projects, $portfolioContent): array {
                        $card = $projects->toGridCard($post, $portfolioContent);
                        $gallery = $projects->galleryFor($post);

                        return [
                            'title' => $card['title'],
                            'thumbUrl' => $card['thumbUrl'],
                            'thumbAlt' => $card['thumbAlt'],
                            'gallerySrcs' => array_column($gallery, 'src'),
                        ];
                    }, $posts);

                    return $overviewRenderer->render(
                        new ServiceContent($languageService->driver()->currentLocale()),
                        $selectedWork
                    );
                },
            ]);

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

            $selectedWorkRenderer = new ServiceSelectedWorkRenderer();

            register_block_type($this->blockDir('service-selected-work'), [
                'render_callback' => static function () use ($selectedWorkRenderer, $languageService): string {
                    // The service and project CPTs use different (but 1:1) slugs for the same four
                    // disciplines — map the current service to its matching project category.
                    $serviceToCategory = [
                        'video-editing' => 'video',
                        'motion-graphics' => 'motion',
                        'graphic-design' => 'design',
                        'website-making' => 'web',
                    ];

                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;
                    $currentSlug = '';
                    if ($queried instanceof \WP_Post) {
                        $meta = get_post_meta($queried->ID, '_perego_service_slug', true);
                        $currentSlug = is_string($meta) && $meta !== '' ? $meta : $queried->post_name;
                    }
                    $category = $serviceToCategory[$currentSlug] ?? '';
                    if ($category === '') {
                        return '';
                    }

                    $locale = $languageService->driver()->currentLocale();

                    // Polylang gives every language its own category term (e.g. "video" for en, a
                    // separate "video-ar" term for ar, linked as translations) — querying by the
                    // English slug alone would only ever match English-tagged projects, leaving the
                    // Arabic service singles with an empty (correctly hidden) section.
                    $enTerm = get_term_by('slug', $category, ProjectPostType::TAXONOMY);
                    $termId = $enTerm ? (int) $enTerm->term_id : 0;
                    if ($termId !== 0 && function_exists('pll_get_term')) {
                        $localized = pll_get_term($termId, $locale);
                        $termId = $localized ? (int) $localized : $termId;
                    }
                    if ($termId === 0) {
                        return '';
                    }

                    $projects = new ProjectRepository();
                    $portfolioContent = new PortfolioContent($locale);

                    $posts = (new \WP_Query([
                        'post_type' => ProjectPostType::POST_TYPE,
                        'post_status' => 'publish',
                        'posts_per_page' => 9,
                        'no_found_rows' => true,
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'tax_query' => [[
                            'taxonomy' => ProjectPostType::TAXONOMY,
                            'field' => 'term_id',
                            'terms' => $termId,
                        ]],
                    ]))->posts;

                    $selectedWork = array_map(static function (\WP_Post $post) use ($projects, $portfolioContent): array {
                        $card = $projects->toGridCard($post, $portfolioContent);
                        $gallery = $projects->galleryFor($post);

                        return [
                            'title' => $card['title'],
                            'thumbUrl' => $card['thumbUrl'],
                            'thumbAlt' => $card['thumbAlt'],
                            'gallerySrcs' => array_column($gallery, 'src'),
                        ];
                    }, $posts);

                    return $selectedWorkRenderer->render($selectedWork);
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

            register_block_type($this->blockDir('project-hero'), [
                'render_callback' => static function () use ($languageService): string {
                    $content = new PortfolioContent($languageService->driver()->currentLocale());
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;

                    return (new ProjectHeroRenderer($content))->render(
                        $queried instanceof \WP_Post ? $queried : null
                    );
                },
            ]);

            register_block_type($this->blockDir('project-gallery-lightbox'), [
                'render_callback' => static function () use ($languageService): string {
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;
                    if (! $queried instanceof \WP_Post) {
                        return '';
                    }

                    $content = new PortfolioContent($languageService->driver()->currentLocale());
                    $labels = $content->projectLabels();

                    return (new ProjectGalleryLightboxRenderer())->render(
                        (new ProjectRepository())->galleryFor($queried),
                        [
                            'sectionLabel' => $content->projectLabel('galleryTitle') ?: 'Project gallery',
                            'close' => $content->projectLabel('close') ?: 'Close',
                            'prev' => (string) $labels['prev'],
                            'next' => (string) $labels['next'],
                            'counter' => '%1$s / %2$s',
                        ],
                    );
                },
            ]);

            register_block_type($this->blockDir('project-navigation'), [
                'render_callback' => static function (array $attributes) use ($languageService): string {
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;

                    return (new ProjectNavigationRenderer(
                        new ProjectRepository(),
                        new PortfolioContent($languageService->driver()->currentLocale()),
                    ))->render($queried instanceof \WP_Post ? $queried : null, (string) ($attributes['surface'] ?? 'all'));
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

            $footerRenderer = new SiteFooterRenderer($this->languageService);
            register_block_type($this->blockDir('site-footer'), [
                'render_callback' => static function (array $attributes) use ($footerRenderer): string {
                    return $footerRenderer->render((bool) ($attributes['flat'] ?? false));
                },
            ]);

            $preloaderRenderer = new PreloaderRenderer();
            register_block_type($this->blockDir('preloader'), [
                'render_callback' => static fn (): string => $preloaderRenderer->render(),
            ]);

            $lightboxRenderer = new MediaLightboxRenderer();
            $languageService = $this->languageService;
            register_block_type($this->blockDir('media-lightbox'), [
                'render_callback' => static function () use ($lightboxRenderer, $languageService): string {
                    $content = new GlobalContent($languageService->driver()->currentLocale());

                    return $lightboxRenderer->render($content->lightbox());
                },
            ]);
        });
    }

    private function registerContactServiceChooser(): void
    {
        add_action('init', function (): void {
            register_block_type($this->blockDir('contact-service-chooser'), [
                'render_callback' => static fn (): string => (new ContactServiceChooserRenderer())->render(),
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

            $aboutBgRenderer = new HomeAboutBgRenderer();
            register_block_type($this->blockDir('home-about-bg'), [
                'render_callback' => static fn (): string => $aboutBgRenderer->render(),
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
