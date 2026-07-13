<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

/**
 * Homepage copy (M2 / spec 002), locale-aware. Mirrors the design handoff's
 * `content/{en,ar}.json → home` — the handoff package itself is gitignored reference material, so the
 * source-of-truth strings are inlined here rather than read from disk at runtime. Falls back to `en`
 * for any locale we don't ship copy for. A later spec can swap this static source for options/CPT
 * without changing the block renderers that consume it.
 *
 * @phpstan-type Slide array{title: string, text: string}
 * @phpstan-type Service array{slug: string, name: string, alt: string}
 */
final class HomeContent
{
    private const DEFAULT_LOCALE = 'en';

    /**
     * The four services, in the fixed display order the handoff mandates. Slugs match the service
     * single routes the header already links to (spec 001). `image` is the teaser card asset base
     * name; `alt` is locale-independent structural text kept in English until AR alt copy is supplied.
     *
     * @var list<array{slug: string, key: string, image: string, alt: string}>
     */
    private const SERVICE_ORDER = [
        ['slug' => 'video-editing', 'key' => 'videoEditing', 'image' => 'card-video-editing', 'alt' => 'Video editing timeline'],
        ['slug' => 'motion-graphics', 'key' => 'motionGraphics', 'image' => 'card-motion-graphics', 'alt' => '2D motion graphics workspace'],
        ['slug' => 'graphic-design', 'key' => 'graphicDesign', 'image' => 'card-graphic-design', 'alt' => 'Graphic design on a laptop'],
        ['slug' => 'website-making', 'key' => 'websiteMaking', 'image' => 'card-website-making', 'alt' => 'Website code editor'],
    ];

    /**
     * @var array<string, array{
     *   hero: array{slides: list<array{title: string, text: string}>, cta: string},
     *   servicesTeaser: array{title: string, seeAll: string},
     *   services: array<string, string>
     * }>
     */
    private const COPY = [
        'en' => [
            'hero' => [
                'slides' => [
                    [
                        'title' => 'What We Believe',
                        'text' => 'Not every artist holds a brush. A brush is only a tool; what truly matters are the ideas you bring to life. You can be an artist through your mindset, your dedication, your passion, and the way you shape your priorities. Art lives in what you have mastered or in what you have yet to discover, but it is already within you.',
                    ],
                    [
                        'title' => 'Ideas, In Motion',
                        'text' => 'From the first spark of a concept to the final frame, we shape stories that move people. Video editing, motion graphics, design and web — one studio, one obsessive standard for craft, delivered on time across the Arab region.',
                    ],
                    [
                        'title' => 'Built To Be Seen',
                        'text' => 'We turn brands into experiences worth watching. Bold visuals, clear messages, and creative technical solutions tailored to exactly what your audience needs — crafted to elevate your business and your digital presence.',
                    ],
                ],
                'cta' => 'Say Hello!',
            ],
            'servicesTeaser' => [
                'title' => 'Services we can help you with',
                'seeAll' => 'See All Services',
            ],
            'services' => [
                'videoEditing' => 'Video Editing',
                'motionGraphics' => '2D Motion Graphics',
                'graphicDesign' => 'Graphic Design',
                'websiteMaking' => 'Website Making',
            ],
        ],
        'ar' => [
            'hero' => [
                'slides' => [
                    [
                        'title' => 'بماذا نؤمن',
                        'text' => 'ليس كل فنان يمسك فرشاة؛ فالفرشاة مجرد أداة، وما يهم حقًا هو الأفكار التي تمنحها الحياة. يمكنك أن تكون فنانًا بعقليتك وتفانيك وشغفك وطريقتك في ترتيب أولوياتك. الفن يكمن فيما أتقنته أو فيما لم تكتشفه بعد، لكنه موجود بداخلك بالفعل.',
                    ],
                    [
                        'title' => 'أفكار تتحرك',
                        'text' => 'من أول شرارة للفكرة حتى اللقطة الأخيرة، نصنع قصصًا تُحرّك المشاعر. مونتاج فيديو، وموشن جرافيك، وتصميم، ومواقع — استوديو واحد بمعيار واحد لا يهادن في جودة الصنعة، ويُسلَّم في موعده في جميع أنحاء المنطقة العربية.',
                    ],
                    [
                        'title' => 'صُنع ليُرى',
                        'text' => 'نحوّل العلامات التجارية إلى تجارب تستحق المشاهدة. عناصر بصرية جريئة، ورسائل واضحة، وحلول تقنية إبداعية مصممة تمامًا وفق ما يحتاجه جمهورك — لنرتقي بأعمالك وحضورك الرقمي.',
                    ],
                ],
                'cta' => 'قل مرحبًا!',
            ],
            'servicesTeaser' => [
                'title' => 'خدمات يمكننا مساعدتك بها',
                'seeAll' => 'عرض كل الخدمات',
            ],
            'services' => [
                'videoEditing' => 'مونتاج الفيديو',
                'motionGraphics' => 'موشن جرافيك ثنائي الأبعاد',
                'graphicDesign' => 'التصميم الجرافيكي',
                'websiteMaking' => 'إنشاء المواقع',
            ],
        ],
    ];

    private readonly string $locale;

    public function __construct(string $locale = self::DEFAULT_LOCALE)
    {
        $this->locale = isset(self::COPY[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    /**
     * @return list<array{title: string, text: string}>
     */
    public function heroSlides(): array
    {
        return self::COPY[$this->locale]['hero']['slides'];
    }

    public function heroCta(): string
    {
        return self::COPY[$this->locale]['hero']['cta'];
    }

    public function servicesTeaserTitle(): string
    {
        return self::COPY[$this->locale]['servicesTeaser']['title'];
    }

    public function servicesTeaserSeeAll(): string
    {
        return self::COPY[$this->locale]['servicesTeaser']['seeAll'];
    }

    /**
     * The four service cards, in fixed order, with the locale's name and the card image/alt.
     *
     * @return list<array{slug: string, name: string, image: string, alt: string}>
     */
    public function services(): array
    {
        $names = self::COPY[$this->locale]['services'];

        return array_map(
            static fn (array $service): array => [
                'slug' => $service['slug'],
                'name' => $names[$service['key']],
                'image' => $service['image'],
                'alt' => $service['alt'],
            ],
            self::SERVICE_ORDER,
        );
    }
}
