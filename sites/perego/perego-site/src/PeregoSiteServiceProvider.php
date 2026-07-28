<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite;

defined('ABSPATH') || exit;

use PeregoSite\Blocks\HeroSliderRenderer;
use PeregoSite\Blocks\HomeAboutBgRenderer;
use PeregoSite\Blocks\HomeAboutRenderer;
use PeregoSite\Blocks\PortfolioGridRenderer;
use PeregoSite\Blocks\PostBreadcrumbRenderer;
use PeregoSite\Blocks\PostReadingTimeRenderer;
use PeregoSite\Blocks\ClientsCarouselRenderer;
use PeregoSite\Blocks\ContactServiceChooserRenderer;
use PeregoSite\Blocks\JournalHeaderRenderer;
use PeregoSite\Blocks\LocalizedAttributes;
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
use PeregoSite\Blocks\FooterCareersRenderer;
use PeregoSite\Content\ClientsContent;
use PeregoSite\Content\GlobalContent;
use PeregoSite\Content\PortfolioContent;
use PeregoSite\Content\ServiceContent;
use PeregoSite\Content\ServicePortfolioSelection;
use PeregoSite\PostTypes\ClientPostType;
use PeregoSite\PostTypes\ProjectPostType;
use PeregoSite\PostTypes\ServicePostType;
use PeregoSite\Repositories\ProjectRepository;
use PeregoSite\Seo\StructuredData;
use PeregoSite\Services\LanguageService;
use WP_Block;

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

        // Must be register(), not boot(): corex-config resolves its DataRegistry during ITS boot (the
        // Overview renderer pulls it in), and that singleton reads ManagedTables once, at build time. A
        // table registered in our boot() — let alone on `init` — arrives after the registry is sealed and
        // never reaches the Data screen. Every provider registers before any provider boots, so this is
        // the last moment that still counts.
        self::registerApplicationsTable();
    }

    public function boot(): void
    {
        $this->registerGlobalShellBlocks();
        $this->registerHomeBlocks();
        $this->registerPortfolio();
        $this->registerServices();
        $this->registerTranslatablePostTypes();
        $this->registerGlobalSurfaces();
        $this->registerClients();
        $this->registerContactServiceChooser();
        $this->registerForms();

        $this->registerTemplateSectionAttributes();

        (new StructuredData())->register();
        (new \PeregoSite\Seo\PeregoAgentReadiness())->register();
        (new \PeregoSite\Seo\PeregoMeta($this->languageService->driver()->currentLocale()))->register();
        (new \PeregoSite\Seo\PlaceholderPageIndexing())->register();

        // spec 021 Phase 4 (T019/T022): the structured Project/Service/Client metadata is edited in
        // typed, grouped panels in the block editor's document sidebar. These replace the four classic
        // meta boxes this used to register (PostMetaBoxes, ProjectGalleryMetaBox, ClientMediaMetaBox,
        // ServicePortfolioMetaBox) — every field is `show_in_rest` post meta, so the panels need no
        // nonce and no save handler, and no meta key changed. The Client gallery/video repeater keeps
        // its dedicated box for now: its value is a list of typed objects, not a flat ID list, and it
        // is the one surface the shared primitives do not yet cover.
        if (is_admin()) {
            (new \PeregoSite\Admin\FieldPanels())->register();
            (new \PeregoSite\Admin\PostListColumns())->register();
            (new \PeregoSite\Admin\ClientMediaMetaBox())->register();
        }
    }

    /**
     * Resolve one of a block's prefixed link attributes to `{href, target}` for a renderer that takes
     * already-resolved data (spec 021 T036). Renderers that hold their own LanguageService build their
     * own `LinkTarget`; these pure ones are handed the result instead, so they stay unit-testable.
     *
     * `hrefIfSet` (not `href`) so an unconfigured link yields an empty href and the renderer keeps its
     * own default route untouched — see that method for why the difference is not cosmetic.
     *
     * @param array<string, mixed> $attributes the block's attributes
     * @return array{href: string, target: string}
     */
    private static function resolvedLink(
        LanguageService $languageService,
        array $attributes,
        string $prefix
    ): array {
        $target = new \PeregoSite\Blocks\LinkTarget($languageService->driver());
        $link = \PeregoSite\Blocks\LinkTarget::fromAttributes($attributes, $prefix);

        return [
            'href' => $target->hrefIfSet($link),
            'target' => $target->targetAttributes($link),
        ];
    }

    /**
     * spec 021 T035: put back the structural attributes the theme templates need but `core/group`'s
     * `save()` cannot generate, so the templates hold exactly what the block editor regenerates and
     * stop rendering as "unexpected or invalid content". See TemplateSectionAttributes for the full
     * rationale. Front-end only — the editor canvas has no need for them.
     */
    private function registerTemplateSectionAttributes(): void
    {
        $sections = new \PeregoSite\Theme\TemplateSectionAttributes();
        $optional = new \PeregoSite\Forms\OptionalFieldMarker();
        $moved = new \PeregoSite\Theme\LegacyRouteRedirect();

        // /contact -> /start-a-project (2026-07-26). Runs before the old path can 404.
        add_action('template_redirect', static fn () => $moved->maybeRedirect());

        add_filter('render_block', static function ($html, $block) use ($sections, $optional) {
            $block = is_array($block) ? $block : [];
            $html = $sections->apply((string) $html, $block);

            // "(optional)" on non-required labels — see OptionalFieldMarker for why this is done here
            // rather than in corex-forms, which this site must not edit.
            if (($block['blockName'] ?? '') === \PeregoSite\Forms\OptionalFieldMarker::BLOCK_NAME) {
                $html = $optional->mark($html);
            }

            return $html;
        }, 10, 2);
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

            // Perego's forms request the `max_words` rule, which is not a CoreX built-in. Register it on
            // the shared RuleRegistry singleton (the same instance SchemaResolver + Validator resolve) here
            // — client-side, so a framework update never removes it — before the forms are rendered or
            // validated. Without it, SchemaResolver throws "Unknown validation rule" and the form renders empty.
            if ($container->has(\Corex\Forms\Validation\RuleRegistry::class)) {
                $rules = $container->make(\Corex\Forms\Validation\RuleRegistry::class);
                if (! $rules->has('max_words')) {
                    $rules->register('max_words', new \PeregoSite\Forms\Rules\MaxWords());
                }
            }

            $registry = $container->make(\Corex\Forms\FormRegistry::class);
            $registry->register(new \PeregoSite\Forms\QuickMessageForm());
            $registry->register(new \PeregoSite\Forms\ProjectBriefForm());

            // Surface Perego's code-registered forms in the CoreX admin Submissions/Data form filter.
            // These are FormRegistry forms (not DB flows), so they carry no flow id; `id: 0` tells the
            // filter to match on `corex_form_slug`. Depends on the `corex_submission_filter_options`
            // hook (CoreX >= the submission-filter enhancement); a no-op on frameworks without it.
            add_filter('corex_submission_filter_options', static function (array $options) use ($container): array {
                if (! $container->has(\Corex\Forms\FormRegistry::class)) {
                    return $options;
                }
                foreach ($container->make(\Corex\Forms\FormRegistry::class)->all() as $form) {
                    $options[] = ['id' => 0, 'name' => $form->label(), 'slug' => $form->slug];
                }

                // Careers is a REST endpoint, not a registered Form, so it is not in the loop above —
                // but it does write submissions (PeregoCareersController::recordSubmission), and a
                // submission the filter cannot name is a submission nobody finds.
                // The label is a literal, not the FORM_NAME constant: `wp i18n make-pot` extracts by
                // parsing the source, so a constant argument yields no POT entry and no translation.
                $options[] = [
                    'id' => 0,
                    'name' => __('Careers application', 'perego-site'),
                    'slug' => \PeregoSite\Careers\PeregoCareersController::SUBMISSION_SLUG,
                ];

                return $options;
            });

            self::registerFormEmail($container);
        }, 20);
    }

    /**
     * Surface job applications in CoreX → Data (client request 2026-07-27: "where can I find it in the
     * admin?").
     *
     * The careers add-on creates `corex_applications` with the Migrator but never marks it managed, so
     * the Data screen — which lists only registered ManagedTables — had no idea it existed, and there is
     * no other admin surface for applications anywhere (spec 014 defers a recruiter screen; spec 017
     * defers the applications DataView). Registering it here is the framework's own opt-in API and needs
     * no framework edit.
     *
     * `cv_attachment` is the stored attachment id. It renders as a bare integer until the Data screen
     * learns to render attachment columns as download links — tracked as a CoreX framework fix; until
     * then the careers notification email carries the working download link.
     */
    private static function registerApplicationsTable(): void
    {
        if (! class_exists(\Corex\Boot::class)) {
            return;
        }

        $container = \Corex\Boot::app()->container();
        if (! $container->has(\Corex\Database\Schema\ManagedTables::class)) {
            return;
        }

        $container->make(\Corex\Database\Schema\ManagedTables::class)->register(
            new \Corex\Database\Schema\ManagedTable(
                'applications',
                'Applications',
                [
                    ['id' => 'name', 'label' => 'Name'],
                    ['id' => 'email', 'label' => 'Email'],
                    ['id' => 'cover_letter', 'label' => 'Portfolio'],
                    ['id' => 'cv_attachment', 'label' => 'CV'],
                    ['id' => 'status', 'label' => 'Status'],
                    ['id' => 'created_at', 'label' => 'Received'],
                ],
                'perego-site',
            ),
        );
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

        // Both the logo and every CTA are rebased onto the public mail base URL: an inbox cannot resolve
        // the host this request happened to arrive on. See MailBaseUrl for why the site *option* rather
        // than home_url() is the floor.
        $logoUrl = function_exists('plugins_url')
            ? \PeregoSite\Email\MailBaseUrl::rebase(
                plugins_url('assets/email/logo-full.png', dirname(__DIR__) . '/perego-site.php')
            )
            : '';
        $siteUrl = \PeregoSite\Email\MailBaseUrl::resolve();

        $mailer = new \PeregoSite\Email\PeregoMailer(
            $container->make(\Corex\Mail\Mailer::class),
            new \PeregoSite\Email\PeregoEmailRenderer($siteUrl, $logoUrl),
        );

        // Where internal mail lands: `forms.email.recipient` when configured, else the site admin. One
        // resolver shared by the forms listener and the careers endpoint, so the two cannot drift.
        $recipient = new \PeregoSite\Email\TeamRecipient(
            $container->has(\Corex\Support\Config\ConfigInterface::class)
                ? $container->make(\Corex\Support\Config\ConfigInterface::class)
                : null,
        );

        $container->make(\Corex\Events\ListenerProvider::class)->listen(
            \Corex\Forms\Submission\FormSubmittedEvent::class,
            new \PeregoSite\Email\PeregoFormMailListener($mailer, $recipient),
        );

        // Branded comment-moderation email (replaces WordPress's plain native notifications).
        (new \PeregoSite\Email\PeregoCommentNotifier($mailer))->register();

        self::registerReplyGateway($container, $mailer);

        // The "Join us" / CV submission endpoint (secure upload → store → branded emails).
        add_action('rest_api_init', static function () use ($mailer, $recipient): void {
            (new \PeregoSite\Careers\PeregoCareersController($mailer, $recipient))->register();
            // Secure AJAX comment submission (nonce + honeypot + rate limit + WP moderation).
            (new \PeregoSite\Comments\PeregoCommentController())->register();
        });
    }

    /**
     * Re-point the Submissions-inbox reply at Perego's own branded template.
     *
     * `SubmissionEmailGateway` is the framework's documented seam for this: corex-config binds an
     * unavailable stub, corex-email overrides it with the Email Studio gateway, and a site may override
     * it in turn — which is client-site composition, not a framework edit. Re-binding drops the cached
     * singleton, and nothing resolves this seam until an admin opens the inbox, so replacing it here on
     * `init` is in time.
     *
     * The decorator keeps the engine's gateway for `resend()`/`log()`; it is depended on by its concrete
     * class rather than by the interface, because asking the container for the interface inside its own
     * factory would resolve straight back into this closure.
     */
    private static function registerReplyGateway(
        \Corex\Container\ContainerInterface $container,
        \PeregoSite\Email\PeregoMailer $mailer,
    ): void {
        if (! interface_exists(\Corex\Mail\SubmissionEmailGateway::class)) {
            return;
        }

        $container->singleton(
            \Corex\Mail\SubmissionEmailGateway::class,
            static function (\Corex\Container\ContainerInterface $c) use ($mailer): \Corex\Mail\SubmissionEmailGateway {
                $inner = class_exists(\Corex\Email\Studio\EmailStudioSubmissionGateway::class)
                    ? $c->make(\Corex\Email\Studio\EmailStudioSubmissionGateway::class)
                    : new \Corex\Mail\UnavailableSubmissionEmailGateway();

                return new \PeregoSite\Email\PeregoSubmissionEmailGateway($inner, $mailer);
            },
        );
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
     * spec M6: the client CPT (+ type taxonomy) and the perego-theme/clients-carousel block that
     * renders the homepage Corporate + Individual carousels (server-rendered cards + a scroll-snap
     * track/arrow-button enhancement).
     */
    private function registerClients(): void
    {
        add_action('init', function (): void {
            (new ClientPostType())->register();

            $languageService = $this->languageService;

            register_block_type($this->blockDir('clients-carousel'), [
                'render_callback' => static function (array $attributes) use ($languageService): string {
                    $locale = $languageService->driver()->currentLocale();

                    return (new ClientsCarouselRenderer(new ClientsContent($locale), $locale, $attributes))->render();
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

            // spec 009: the footer "Join us" careers editorial, migrated off the perego_section CPT into
            // a bilingual block whose EN/AR variants live in its own attributes.
            register_block_type($this->blockDir('footer-careers'), [
                'render_callback' => static function (array $attributes) use ($languageService): string {
                    return (new FooterCareersRenderer(
                        $languageService->driver()->currentLocale()
                    ))->render($attributes);
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

            // spec 017: the legal-hero "Last updated: <date>" line, an editable projection of the page's
            // curated `_perego_legal_updated` meta (falls back to the modified date), per the handoff.
            (new \PeregoSite\Blocks\LegalUpdatedRenderer(
                new GlobalContent($languageService->driver()->currentLocale())
            ))->register();
            register_block_type($this->blockDir('legal-updated'), [
                'render_callback' => static function () use ($languageService): string {
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;

                    return (new \PeregoSite\Blocks\LegalUpdatedRenderer(
                        new GlobalContent($languageService->driver()->currentLocale())
                    ))->render($queried instanceof \WP_Post ? $queried : null);
                },
            ]);

            register_block_type($this->blockDir('journal-header'), [
                'render_callback' => static function (array $attributes) use ($languageService): string {
                    $locale = $languageService->driver()->currentLocale();

                    // spec 021 C12: the archive title/lead are editable per locale; an empty field
                    // falls back to the seed copy, so an unedited block is unchanged.
                    return (new JournalHeaderRenderer(
                        new GlobalContent($locale),
                        LocalizedAttributes::pick($attributes, $locale, ['title', 'lead']),
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

            register_block_type($this->blockDir('related-posts'), [
                'render_callback' => static function () use ($languageService): string {
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;
                    if (! $queried instanceof \WP_Post) {
                        return '';
                    }

                    $content = new GlobalContent($languageService->driver()->currentLocale());

                    return (new \PeregoSite\Blocks\RelatedPostsRenderer(
                        $content,
                        new PostReadingTimeRenderer($content),
                    ))->render((new \PeregoSite\Repositories\JournalRepository())->relatedFor($queried));
                },
            ]);

            register_block_type($this->blockDir('journal-comments'), [
                'render_callback' => static function () use ($languageService): string {
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;
                    if (! $queried instanceof \WP_Post) {
                        return '';
                    }

                    $journal = (new GlobalContent($languageService->driver()->currentLocale()))->journal();

                    // Approved comments in insertion order, then threaded: each top-level comment is
                    // followed immediately by its replies (the handoff's indented `.comment--reply`),
                    // rather than a flat date sort that would scatter a reply away from its parent.
                    $rawComments = get_comments([
                        'post_id' => $queried->ID,
                        'status' => 'approve',
                        'type' => 'comment',
                        'orderby' => 'comment_ID',
                        'order' => 'ASC',
                    ]);

                    $children = [];
                    foreach ($rawComments as $comment) {
                        $parent = (int) $comment->comment_parent;
                        if ($parent !== 0) {
                            $children[$parent][] = $comment;
                        }
                    }

                    $ordered = [];
                    foreach ($rawComments as $comment) {
                        if ((int) $comment->comment_parent !== 0) {
                            continue; // Placed under its parent below.
                        }
                        $ordered[] = $comment;
                        foreach ($children[(int) $comment->comment_ID] ?? [] as $child) {
                            $ordered[] = $child;
                        }
                    }

                    $comments = array_map(static fn (\WP_Comment $comment): array => [
                        'id' => (int) $comment->comment_ID,
                        'author' => $comment->comment_author,
                        'date' => (string) get_comment_date('', $comment),
                        'text' => $comment->comment_content,
                        'isReply' => (int) $comment->comment_parent !== 0,
                    ], $ordered);

                    return (new \PeregoSite\Blocks\JournalCommentsRenderer())->render($comments, [
                        'title' => $journal['commentsTitle'],
                        'titleOne' => $journal['commentsTitleOne'],
                        'reply' => $journal['reply'],
                        'replyTo' => $journal['replyTo'],
                        'postId' => (int) $queried->ID,
                        // Form + AJAX wiring (view.js reads endpoint/nonce/messages off the section).
                        'leaveComment' => $journal['leaveComment'],
                        'replyingTo' => $journal['replyingTo'],
                        'cancel' => $journal['cancel'],
                        'nameLabel' => $journal['nameLabel'],
                        'namePlaceholder' => $journal['namePlaceholder'],
                        'emailLabel' => $journal['emailLabel'],
                        'emailNote' => $journal['emailNote'],
                        'emailPlaceholder' => $journal['emailPlaceholder'],
                        'commentLabel' => $journal['commentLabel'],
                        'commentPlaceholder' => $journal['commentPlaceholder'],
                        'submit' => $journal['submit'],
                        'hpLabel' => $journal['hpLabel'],
                        'commentsPostUrl' => site_url('/wp-comments-post.php'),
                        'endpoint' => rest_url('perego/v1/comments'),
                        'nonce' => wp_create_nonce('wp_rest'),
                        // Status/error messages + the count-title formats view.js needs to re-render
                        // the "N Comments" heading after injecting an approved comment on the fly.
                        'messagesJson' => (string) wp_json_encode($journal['commentStatus'] + [
                            '__title' => $journal['commentsTitle'],
                            '__titleOne' => $journal['commentsTitleOne'],
                        ]),
                    ]);
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

            register_block_type($this->blockDir('post-share'), [
                'render_callback' => static function (): string {
                    // Same reason as post-reading-time above: get_post() resolves the current post in
                    // both singular and Query Loop contexts, get_queried_object() does not.
                    $current = function_exists('get_post') ? get_post() : null;

                    return (new \PeregoSite\Blocks\PostShareRenderer())
                        ->render($current instanceof \WP_Post ? $current : null);
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
            $selectedWorkRenderer = new ServiceSelectedWorkRenderer();
            $webShowcaseRenderer = new \PeregoSite\Blocks\WebShowcaseRenderer();
            $overviewRenderer = new ServicesOverviewRenderer($selectedWorkRenderer);

            register_block_type($this->blockDir('services-overview'), [
                'render_callback' => static function () use ($overviewRenderer, $languageService): string {
                    $locale = $languageService->driver()->currentLocale();
                    $projects = new ProjectRepository();
                    $portfolioContent = new PortfolioContent($locale);

                    // 15 designed mosaic placements + up to 8 "Load more" overflow tiles (handoff
                    // services.html masonry parity).
                    $posts = (new \WP_Query([
                        'post_type' => ProjectPostType::POST_TYPE,
                        'post_status' => 'publish',
                        'posts_per_page' => 23,
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
                            'videoUrl' => $projects->videoUrlFor($post),
                        ];
                    }, $posts);

                    return $overviewRenderer->render(
                        new ServiceContent($locale),
                        $selectedWork,
                        (new \PeregoSite\Content\ServiceCatalog())->labelsBySlug($locale)
                    );
                },
            ]);

            register_block_type($this->blockDir('service-hero'), [
                'render_callback' => static function () use ($heroRenderer, $languageService): string {
                    $locale  = $languageService->driver()->currentLocale();
                    $content = new ServiceContent($locale);
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;
                    $servicePost = $queried instanceof \WP_Post ? $queried : get_post();

                    // Resolve the canonical service slug from meta, not post_name: a translated
                    // (e.g. Arabic) service post carries a Polylang-de-duplicated slug like
                    // "video-editing-2", but _perego_service_slug always holds the canonical
                    // "video-editing" the ServiceContent map + tab routes are keyed on.
                    $currentSlug  = '';
                    $currentTitle = '';
                    if ($servicePost instanceof \WP_Post) {
                        $meta = get_post_meta($servicePost->ID, '_perego_service_slug', true);
                        $currentSlug = is_string($meta) && $meta !== '' ? $meta : $servicePost->post_name;
                        // spec 013: the H1 is the Service post's own title (edit it natively); the tab
                        // labels come from each Service's editable teaser label — both seed-fallback.
                        // Use the raw post_title (not get_the_title) so the renderer's single esc_html
                        // matches the old ServiceContent path byte-for-byte (no double entity-encoding).
                        $currentTitle = (string) $servicePost->post_title;
                    }

                    $tabLabels = (new \PeregoSite\Content\ServiceCatalog())->labelsBySlug($locale);

                    return $heroRenderer->render($content, $currentSlug, $currentTitle, $tabLabels);
                },
            ]);

            register_block_type($this->blockDir('service-selected-work'), [
                'render_callback' => static function () use ($selectedWorkRenderer, $webShowcaseRenderer, $languageService): string {
                    // The service and project CPTs use different (but 1:1) slugs for the same four
                    // disciplines — map the current service to its matching project category.
                    $serviceToCategory = [
                        'video-editing' => 'video',
                        'motion-graphics' => 'motion',
                        'graphic-design' => 'design',
                        'website-making' => 'web',
                    ];

                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;
                    $servicePost = $queried instanceof \WP_Post ? $queried : get_post();
                    $currentSlug = '';
                    if ($servicePost instanceof \WP_Post) {
                        $meta = get_post_meta($servicePost->ID, '_perego_service_slug', true);
                        $currentSlug = is_string($meta) && $meta !== '' ? $meta : $servicePost->post_name;
                    }

                    // The Service editor owns the optional portfolio projection. Translation
                    // records may inherit the English source until they receive their own selection.
                    $portfolioMeta = static function (string $key) use ($servicePost) {
                        if (! $servicePost instanceof \WP_Post) {
                            return '';
                        }

                        $value = get_post_meta($servicePost->ID, $key, true);
                        if ($value !== '' && $value !== []) {
                            return $value;
                        }
                        if (! function_exists('pll_get_post')) {
                            return $value;
                        }
                        $englishId = (int) pll_get_post($servicePost->ID, 'en');

                        return $englishId > 0 && $englishId !== $servicePost->ID ? get_post_meta($englishId, $key, true) : $value;
                    };
                    $portfolioMode = ServicePostType::sanitizePortfolioMode($portfolioMeta(ServicePostType::META_PORTFOLIO_MODE));
                    $portfolioIds = ServicePostType::sanitizeIntList($portfolioMeta(ServicePostType::META_PORTFOLIO_PROJECT_IDS));
                    $portfolioExclusions = ServicePostType::sanitizeIntList($portfolioMeta(ServicePostType::META_PORTFOLIO_EXCLUDE_IDS));
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

                    // The website service shows the full logo wall; every other service shows a
                    // short, curated set. The client asked for "minimum 5 and not more than 6" there
                    // — a service page is a pitch, not an archive, and 23 tiles behind a "Load more"
                    // button buried the work it was meant to lead with.
                    $workCap = $currentSlug === 'website-making' ? 23 : 6;

                    $posts = (new \WP_Query([
                        'post_type' => ProjectPostType::POST_TYPE,
                        'post_status' => 'publish',
                        'posts_per_page' => $workCap,
                        'no_found_rows' => true,
                        'orderby' => 'date',
                        'order' => 'DESC',
                        'tax_query' => [[
                            'taxonomy' => ProjectPostType::TAXONOMY,
                            'field' => 'term_id',
                            'terms' => $termId,
                        ]],
                    ]))->posts;

                    $selectedPosts = [];
                    if ($portfolioMode !== 'automatic' && function_exists('get_post')) {
                        foreach ($portfolioIds as $projectId) {
                            $localizedId = function_exists('pll_get_post') ? (int) pll_get_post($projectId, $locale) : $projectId;
                            $project = get_post($localizedId ?: $projectId);
                            if ($project instanceof \WP_Post && $project->post_type === ProjectPostType::POST_TYPE && $project->post_status === 'publish') {
                                $selectedPosts[] = $project;
                            }
                        }
                    }
                    $posts = array_slice(
                        (new ServicePortfolioSelection())->resolve($posts, $selectedPosts, $portfolioMode, $portfolioExclusions),
                        0,
                        $workCap,
                    );

                    $content = new ServiceContent($locale);

                    // The Website-Making single's last section is the handoff's unique web-showcase
                    // (browser-chrome cards + type filters), not the Selected-work masonry.
                    if ($currentSlug === 'website-making') {
                        return $webShowcaseRenderer->render(
                            $content,
                            array_map(
                                static fn (\WP_Post $post): array => $projects->toWebCard($post),
                                $posts,
                            ),
                        );
                    }

                    $portfolioContent = new PortfolioContent($locale);
                    $selectedWork = array_map(static function (\WP_Post $post) use ($projects, $portfolioContent): array {
                        $card = $projects->toGridCard($post, $portfolioContent);
                        $gallery = $projects->galleryFor($post);

                        return [
                            'title' => $card['title'],
                            'thumbUrl' => $card['thumbUrl'],
                            'thumbAlt' => $card['thumbAlt'],
                            'gallerySrcs' => array_column($gallery, 'src'),
                            'videoUrl' => $projects->videoUrlFor($post),
                        ];
                    }, $posts);

                    return $selectedWorkRenderer->render($selectedWork, [
                        'loadMore' => $content->label('loadMore'),
                        'galleryBadge' => $content->label('galleryBadge'),
                    ]);
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
                'render_callback' => static function (array $attributes) use ($gridRenderer, $languageService): string {
                    $locale   = $languageService->driver()->currentLocale();
                    $content  = new PortfolioContent($locale);
                    $projects = (new ProjectRepository())->allForGrid($content);

                    // spec 021 C11: the archive heading/intro and closing CTA are editable per locale;
                    // an empty field falls back to the seed copy, so an unedited block is unchanged.
                    $strings = $content->gridStrings(LocalizedAttributes::pick($attributes, $locale, [
                        'heading', 'intro', 'ctaTitle', 'ctaBody', 'ctaButton',
                    ]) + ['showDemoNote' => (bool) ($attributes['showDemoNote'] ?? true)]);

                    if (! (bool) ($attributes['showBreadcrumb'] ?? true)) {
                        $strings['uiHome'] = '';
                    }

                    return $gridRenderer->render(
                        $projects,
                        $content->filterLabels(),
                        $strings,
                        // spec 021 T036: the closing CTA may name a page instead of the contact route.
                        self::resolvedLink($languageService, $attributes, 'cta'),
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

                    return (new ProjectGalleryLightboxRenderer())->render(
                        (new ProjectRepository())->galleryFor($queried),
                        $content->projectLabel('galleryTitle') ?: 'Project gallery',
                    );
                },
            ]);

            register_block_type($this->blockDir('project-navigation'), [
                'render_callback' => static function (array $attributes) use ($languageService): string {
                    $queried = function_exists('get_queried_object') ? get_queried_object() : null;

                    return (new ProjectNavigationRenderer(
                        new ProjectRepository(),
                        new PortfolioContent($languageService->driver()->currentLocale()),
                    ))->render(
                        $queried instanceof \WP_Post ? $queried : null,
                        (string) ($attributes['surface'] ?? 'all'),
                        self::resolvedLink($languageService, $attributes, 'cta'),
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
                'render_callback' => static function (array $attributes) use ($headerRenderer): string {
                    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

                    return $headerRenderer->render($path !== '' ? $path : '/', $attributes);
                },
            ]);

            $footerRenderer = new SiteFooterRenderer($this->languageService);
            register_block_type($this->blockDir('site-footer'), [
                'render_callback' => static function (array $attributes) use ($footerRenderer): string {
                    return $footerRenderer->render((bool) ($attributes['flat'] ?? false), $attributes);
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
            $chooserRenderer = new ContactServiceChooserRenderer($this->languageService);
            register_block_type($this->blockDir('contact-service-chooser'), [
                'render_callback' => static fn (): string => $chooserRenderer->render(),
            ]);
        });
    }

    /**
     * spec 002 (M2): the homepage hero slider + services teaser.
     */
    private function registerHomeBlocks(): void
    {
        add_action('init', function (): void {
            // spec 012 T004: the hero is an editable projection of the front page — register its
            // slide/CTA meta so each language's Home page can override the HomeContent seed.
            (new \PeregoSite\Content\HeroContent())->register();

            $heroRenderer = new HeroSliderRenderer($this->languageService);
            register_block_type($this->blockDir('hero-slider'), [
                'render_callback' => static fn (array $attributes): string => $heroRenderer->render($attributes),
            ]);

            $teaserRenderer = new ServicesTeaserRenderer($this->languageService);
            register_block_type($this->blockDir('services-teaser'), [
                'render_callback' => static fn (array $attributes): string => $teaserRenderer->render($attributes),
            ]);

            $aboutBgRenderer = new HomeAboutBgRenderer();
            register_block_type($this->blockDir('home-about-bg'), [
                'render_callback' => static fn (): string => $aboutBgRenderer->render(),
            ]);

            // Takes the third argument for its `postId`/`postType` context — that is what tells the
            // block which language's front page to render (see HomeAboutRenderer).
            $aboutRenderer = new HomeAboutRenderer();
            register_block_type($this->blockDir('home-about'), [
                'render_callback' => static fn (array $attributes, string $content, WP_Block $block): string
                    => $aboutRenderer->render($block),
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
