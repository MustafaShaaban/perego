<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

use PeregoSite\PostTypes\ServicePostType;

/**
 * Services copy (spec 003 / M3, US3), locale-aware. Mirrors the handoff
 * `content/{en,ar}.json → services / servicePages / servicesOverview`. Falls back to `en`.
 *
 * Used both as the seed source for the four `perego_service` CPT posts (whose editable
 * block `post_content` is the source of truth once seeded — the editor-canvas rule) and as
 * the localized strings for the shared service chrome (tabs, section labels, closing CTA,
 * services-overview archive) that are structural rather than editorial.
 *
 * Slugs are the ServicePostType::SERVICES keys; the JSON uses camelCase, mapped via SLUG_KEY.
 */
final class ServiceContent
{
    private const DEFAULT_LOCALE = 'en';

    /** slug (route + CPT) => the camelCase key used in the handoff content JSON. */
    private const SLUG_KEY = [
        'video-editing' => 'videoEditing',
        'motion-graphics' => 'motionGraphics',
        'graphic-design' => 'graphicDesign',
        'website-making' => 'websiteMaking',
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    private const COPY = [
        'en' => [
            'labels' => [
                'whatWeDo' => 'What we do',
                'ourProcess' => 'Our Process',
                'startYourProject' => 'Start your project',
                'selectedWork' => 'Selected work',
                'loadMore' => 'Load more',
                'galleryBadge' => 'Gallery',
            ],
            'names' => [
                'videoEditing' => ['name' => 'Video Editing', 'fullName' => 'Video Editing & Post-Production'],
                'motionGraphics' => ['name' => '2D Motion Graphics', 'fullName' => '2D Motion Graphics & Animation'],
                'graphicDesign' => ['name' => 'Graphic Design', 'fullName' => 'Graphic Design & Brand Identity'],
                'websiteMaking' => ['name' => 'Website Making', 'fullName' => 'Website Making'],
            ],
            'pages' => [
                'videoEditing' => [
                    'subline' => 'Video Editing & Post-Production',
                    'p1' => 'We specialize in professional video editing and post-production services.',
                    'p2' => 'We take your raw footage and transform it into compelling visual content through precise editing, pacing, color enhancement, sound design, and creative effects without filming.',
                    'steps' => [
                        ['label' => 'Footage Review', 'desc' => 'We review your raw footage and understand your goals.'],
                        ['label' => 'Creative Editing', 'desc' => 'We edit, structure, and enhance the video to match your vision.'],
                        ['label' => 'Effects & Refinement', 'desc' => 'Motion elements, transitions, sound, and color enhancements.'],
                        ['label' => 'Delivery & Revisions', 'desc' => 'Final delivery on time, revision rounds included.'],
                    ],
                ],
                'motionGraphics' => [
                    'subline' => '2D Motion Graphics & Animation',
                    'p1' => 'We design and animate 2D motion graphics that explain, engage, and bring ideas to life.',
                    'p2' => 'From explainer videos and animated logos to social content and title sequences, we turn concepts into clear, characterful motion — built around a storyboard and tuned to your brand.',
                    'steps' => [
                        ['label' => 'Understanding the Idea', 'desc' => 'We start by understanding your idea, audience, and goals.'],
                        ['label' => 'Concept & Storyboard', 'desc' => 'We shape the narrative and map every scene as a storyboard.'],
                        ['label' => 'Animation & Design', 'desc' => 'We design the visuals and animate them frame by frame.'],
                        ['label' => 'Review & Delivery', 'desc' => 'We refine with your feedback and deliver on time.'],
                    ],
                ],
                'graphicDesign' => [
                    'subline' => 'Graphic Design & Brand Identity',
                    'p1' => 'We craft brand identities and graphic design that make your business recognisable.',
                    'p2' => 'Logos, visual systems, social templates, and print — designed with a clear direction so every touchpoint looks like you.',
                    'steps' => [
                        ['label' => 'Brand Understanding', 'desc' => 'We learn your brand, market, and audience.'],
                        ['label' => 'Visual Direction', 'desc' => 'We define the visual direction, type, and colour.'],
                        ['label' => 'Design Execution', 'desc' => 'We execute the designs across the pieces you need.'],
                        ['label' => 'Review & Delivery', 'desc' => 'We refine with your feedback and hand over the files.'],
                    ],
                ],
                'websiteMaking' => [
                    'subline' => 'Website Making',
                    'p1' => 'We design and build fast, responsive websites tailored to your goals.',
                    'p2' => 'From landing pages to full multi-page sites, we handle design, build, and launch — with creative technical solutions suited to your audience and your business.',
                    'steps' => [
                        ['label' => 'Discovery & Planning', 'desc' => 'We define goals, structure, and content needs.'],
                        ['label' => 'Design', 'desc' => 'We design the pages around your brand and users.'],
                        ['label' => 'Development', 'desc' => 'We build a responsive, performant site.'],
                        ['label' => 'Launch & Support', 'desc' => 'We launch, hand over, and provide support.'],
                    ],
                ],
            ],
            'overview' => [
                'h1' => 'Our Services',
                'introTitle' => 'One studio, four services',
                'introSubline' => 'Video · Motion · Design · Web',
                'introP1' => 'Perego is a creative agency specialising in advertising and digital production for clients across the Arab region.',
                'introP2' => "We bring video editing, 2D motion graphics, graphic design, and website making under one roof — so your brand stays consistent from the first frame to the final page. Choose a service to explore it, or tell us about your project and we'll help you find the right fit.",
                'processTitle' => 'Our Process',
                'processSteps' => [
                    ['label' => 'Discover', 'desc' => 'We learn your brand, goals, and audience.'],
                    ['label' => 'Concept', 'desc' => 'We shape the idea, direction, and plan.'],
                    ['label' => 'Create', 'desc' => 'We produce the work with an obsessive standard for craft.'],
                    ['label' => 'Deliver', 'desc' => 'We refine with your feedback and deliver on time.'],
                ],
                'ctaTitle' => 'Have a project in mind?',
                'ctaBody' => "Tell us what you're working on and we'll help you choose the right service and shape the plan.",
                'ctaButton' => 'Start a Project',
            ],
            // The website-making single's unique last section (handoff service-website-making.html).
            'webShowcase' => [
                'title' => 'Websites we’ve built',
                'intro' => 'A selection of responsive, fast, brand-driven sites we designed and developed — from storefronts to web apps.',
                'filterAria' => 'Filter projects by type',
                'filterAll' => 'All',
                'types' => [
                    'ecommerce' => 'E-Commerce',
                    'corporate' => 'Corporate',
                    'landing' => 'Landing',
                    'webapp' => 'Web App',
                    'portfolio' => 'Portfolio',
                ],
                'visit' => 'Visit',
            ],
        ],
        'ar' => [
            'labels' => [
                'whatWeDo' => 'ماذا نفعل',
                'ourProcess' => 'منهجية عملنا',
                'startYourProject' => 'ابدأ مشروعك',
                'selectedWork' => 'أعمال مختارة',
                'loadMore' => 'عرض المزيد',
                'galleryBadge' => 'معرض',
            ],
            'names' => [
                'videoEditing' => ['name' => 'مونتاج الفيديو', 'fullName' => 'مونتاج الفيديو وما بعد الإنتاج'],
                'motionGraphics' => ['name' => 'موشن جرافيك ثنائي الأبعاد', 'fullName' => 'موشن جرافيك وأنيميشن ثنائي الأبعاد'],
                'graphicDesign' => ['name' => 'التصميم الجرافيكي', 'fullName' => 'التصميم الجرافيكي والهوية البصرية'],
                'websiteMaking' => ['name' => 'إنشاء المواقع', 'fullName' => 'إنشاء المواقع'],
            ],
            'pages' => [
                'videoEditing' => [
                    'subline' => 'مونتاج الفيديو وما بعد الإنتاج',
                    'p1' => 'نحن متخصصون في خدمات مونتاج الفيديو وما بعد الإنتاج الاحترافية.',
                    'p2' => 'نأخذ لقطاتك الخام ونحوّلها إلى محتوى بصري مؤثر عبر مونتاج دقيق، وإيقاع محكم، وتحسين للألوان، وتصميم صوتي، ومؤثرات إبداعية — دون تصوير.',
                    'steps' => [
                        ['label' => 'مراجعة اللقطات', 'desc' => 'نراجع لقطاتك الخام ونفهم أهدافك.'],
                        ['label' => 'مونتاج إبداعي', 'desc' => 'نُمنتِج وننظّم ونحسّن الفيديو ليطابق رؤيتك.'],
                        ['label' => 'مؤثرات وصقل', 'desc' => 'عناصر حركية وانتقالات وصوت وتحسينات لونية.'],
                        ['label' => 'تسليم ومراجعات', 'desc' => 'تسليم نهائي في الموعد، مع جولات مراجعة.'],
                    ],
                ],
                'motionGraphics' => [
                    'subline' => 'موشن جرافيك وأنيميشن ثنائي الأبعاد',
                    'p1' => 'نصمم ونحرّك رسوم موشن جرافيك ثنائية الأبعاد تشرح الأفكار وتجذب الانتباه وتمنحها الحياة.',
                    'p2' => 'من فيديوهات الشرح والشعارات المتحركة إلى محتوى السوشيال والمقدمات، نحوّل المفاهيم إلى حركة واضحة ومميّزة — مبنية على ستوري بورد ومضبوطة على هوية علامتك.',
                    'steps' => [
                        ['label' => 'فهم الفكرة', 'desc' => 'نبدأ بفهم فكرتك وجمهورك وأهدافك.'],
                        ['label' => 'التصوّر والستوري بورد', 'desc' => 'نبلور السرد ونرسم كل مشهد كستوري بورد.'],
                        ['label' => 'الأنيميشن والتصميم', 'desc' => 'نصمم العناصر البصرية ونحرّكها لقطةً بلقطة.'],
                        ['label' => 'المراجعة والتسليم', 'desc' => 'ننقّح بناءً على ملاحظاتك ونسلّم في الموعد.'],
                    ],
                ],
                'graphicDesign' => [
                    'subline' => 'التصميم الجرافيكي والهوية البصرية',
                    'p1' => 'نصنع هويات بصرية وتصاميم جرافيكية تجعل علامتك سهلة التمييز.',
                    'p2' => 'شعارات، وأنظمة بصرية، وقوالب للسوشيال، ومطبوعات — مصممة باتجاه واضح ليعكس كل عنصر شخصيتك.',
                    'steps' => [
                        ['label' => 'فهم العلامة', 'desc' => 'نتعرّف على علامتك وسوقك وجمهورك.'],
                        ['label' => 'الاتجاه البصري', 'desc' => 'نحدد الاتجاه البصري والخطوط والألوان.'],
                        ['label' => 'تنفيذ التصميم', 'desc' => 'ننفّذ التصاميم عبر العناصر التي تحتاجها.'],
                        ['label' => 'المراجعة والتسليم', 'desc' => 'ننقّح بناءً على ملاحظاتك ونسلّم الملفات.'],
                    ],
                ],
                'websiteMaking' => [
                    'subline' => 'إنشاء المواقع',
                    'p1' => 'نصمم ونبني مواقع سريعة ومتجاوبة مصممة وفق أهدافك.',
                    'p2' => 'من صفحات الهبوط إلى المواقع المتعددة الصفحات، نتولى التصميم والبناء والإطلاق — بحلول تقنية إبداعية تناسب جمهورك وأعمالك.',
                    'steps' => [
                        ['label' => 'الاكتشاف والتخطيط', 'desc' => 'نحدد الأهداف والبنية واحتياجات المحتوى.'],
                        ['label' => 'التصميم', 'desc' => 'نصمم الصفحات حول علامتك ومستخدميك.'],
                        ['label' => 'التطوير', 'desc' => 'نبني موقعًا متجاوبًا وعالي الأداء.'],
                        ['label' => 'الإطلاق والدعم', 'desc' => 'نطلق الموقع ونسلّمه ونقدّم الدعم.'],
                    ],
                ],
            ],
            'overview' => [
                'h1' => 'خدماتنا',
                'introTitle' => 'استوديو واحد، أربع خدمات',
                'introSubline' => 'فيديو · موشن · تصميم · ويب',
                'introP1' => 'بيريجو وكالة إبداعية متخصصة في الإعلان والإنتاج الرقمي لعملاء في أنحاء المنطقة العربية.',
                'introP2' => 'نجمع مونتاج الفيديو، والموشن جرافيك ثنائي الأبعاد، والتصميم الجرافيكي، وإنشاء المواقع تحت سقف واحد — لتبقى علامتك متناسقة من أول لقطة حتى آخر صفحة. اختر خدمة لاستكشافها، أو أخبرنا عن مشروعك ونساعدك في اختيار الأنسب.',
                'processTitle' => 'منهجية عملنا',
                'processSteps' => [
                    ['label' => 'اكتشاف', 'desc' => 'نتعرّف على علامتك وأهدافك وجمهورك.'],
                    ['label' => 'تصوّر', 'desc' => 'نبلور الفكرة والاتجاه والخطة.'],
                    ['label' => 'إنتاج', 'desc' => 'ننفّذ العمل بمعيار لا يهادن في جودة الصنعة.'],
                    ['label' => 'تسليم', 'desc' => 'ننقّح بناءً على ملاحظاتك ونسلّم في الموعد.'],
                ],
                'ctaTitle' => 'هل لديك مشروع في ذهنك؟',
                'ctaBody' => 'أخبرنا بما تعمل عليه وسنساعدك في اختيار الخدمة المناسبة وبلورة الخطة.',
                'ctaButton' => 'ابدأ مشروعك',
            ],
            // القسم الأخير الخاص بصفحة إنشاء المواقع (handoff service-website-making.html).
            'webShowcase' => [
                'title' => 'مواقع أنشأناها',
                'intro' => 'مجموعة مختارة من مواقع متجاوبة وسريعة تعكس الهوية، صممناها وطوّرناها — من المتاجر الإلكترونية إلى تطبيقات الويب.',
                'filterAria' => 'تصفية المشاريع حسب النوع',
                'filterAll' => 'الكل',
                'types' => [
                    'ecommerce' => 'متجر إلكتروني',
                    'corporate' => 'موقع شركة',
                    'landing' => 'صفحة هبوط',
                    'webapp' => 'تطبيق ويب',
                    'portfolio' => 'معرض أعمال',
                ],
                'visit' => 'زيارة',
            ],
        ],
    ];

    private readonly string $locale;

    public function __construct(string $locale = self::DEFAULT_LOCALE)
    {
        $this->locale = isset(self::COPY[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    /** Ordered service slugs (the four fixed services, in display order). @return list<string> */
    public function slugs(): array
    {
        return array_keys(ServicePostType::SERVICES);
    }

    public function name(string $slug): string
    {
        return $this->service($slug)['name'] ?? $slug;
    }

    public function fullName(string $slug): string
    {
        return $this->service($slug)['fullName'] ?? $slug;
    }

    public function subline(string $slug): string
    {
        return $this->page($slug)['subline'] ?? '';
    }

    /** @return array{0: string, 1: string} the two "what we do" paragraphs. */
    public function intro(string $slug): array
    {
        $page = $this->page($slug);

        return [$page['p1'] ?? '', $page['p2'] ?? ''];
    }

    /** @return list<array{label: string, desc: string}> the four process steps. */
    public function processSteps(string $slug): array
    {
        return $this->page($slug)['steps'] ?? [];
    }

    public function label(string $key): string
    {
        return self::COPY[$this->locale]['labels'][$key] ?? '';
    }

    /** @return array<string, mixed> the services-overview (archive) page copy. */
    public function overview(): array
    {
        return self::COPY[$this->locale]['overview'];
    }

    /** @return array<string, mixed> the website-making single's web-showcase copy. */
    public function webShowcase(): array
    {
        return self::COPY[$this->locale]['webShowcase'];
    }

    /** @return array<string, string> */
    private function service(string $slug): array
    {
        $key = self::SLUG_KEY[$slug] ?? null;

        return $key !== null ? (self::COPY[$this->locale]['names'][$key] ?? []) : [];
    }

    /** @return array<string, mixed> */
    private function page(string $slug): array
    {
        $key = self::SLUG_KEY[$slug] ?? null;

        return $key !== null ? (self::COPY[$this->locale]['pages'][$key] ?? []) : [];
    }
}
