<?php

/**
 * Seed the legal pages (Terms & Conditions, Privacy Policy) as editor-managed WordPress pages
 * (spec M4 / spec 020). Run with:
 *   wp eval 'require "sites/perego/perego-site/scripts/seed-legal.php";' --path=wp
 *
 * Each H2 is anchored (`s1`, `s2`, …) so the perego-theme/legal-toc block builds the table of contents
 * from the page's own headings. Both EN and AR carry the SAME section structure and real boilerplate
 * copy, and both use the `legal` custom template.
 *
 * IMPORTANT: this is professional **template** copy for a creative/digital agency, not legal advice —
 * it must be reviewed and adapted by qualified legal counsel for the client's jurisdiction before
 * launch. The lead paragraph on each page states this.
 *
 * Legal content is seed-owned pre-launch: re-running UPDATES the body + curated "last updated" date on
 * the existing pages (so copy edits here propagate). Once the client finalises real copy in wp-admin,
 * retire this seeder rather than re-running it.
 *
 * @package PeregoSite
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run through wp eval.\n");
    exit(1);
}

use PeregoSite\Blocks\LegalUpdatedRenderer;

$pllReady = function_exists('pll_set_post_language') && function_exists('pll_get_post')
    && function_exists('pll_save_post_translations')
    && in_array('ar', function_exists('pll_languages_list') ? pll_languages_list() : [], true);

$lastUpdated = ['en' => 'July 1, 2026', 'ar' => '١ يوليو ٢٠٢٦'];

$lead = [
    'en' => 'This is a plain-language template provided for convenience. It is not legal advice; please have it reviewed and adapted by qualified legal counsel for your jurisdiction before you rely on it.',
    'ar' => 'هذه صيغة استرشادية مبسّطة مقدّمة للتيسير، وهي ليست استشارة قانونية. يُرجى مراجعتها وتكييفها بواسطة مستشار قانوني مختص وفق تشريعات بلدك قبل الاعتماد عليها.',
];

/**
 * Each document: title (EN/AR) + ordered sections, every section { head, body } in EN and AR.
 * `body` is one or more block-editor paragraphs (HTML kept simple: <p>, <strong>).
 */
$documents = [
    'terms' => [
        'title' => ['en' => 'Terms & Conditions', 'ar' => 'الشروط والأحكام'],
        'sections' => [
            [
                'en' => ['Acceptance of Terms', 'By accessing this website or engaging Perego for any service, you agree to be bound by these Terms &amp; Conditions and by any project proposal or statement of work we agree with you in writing. If you do not agree, please do not use the site or our services.'],
                'ar' => ['قبول الشروط', 'بدخولك هذا الموقع أو تعاقدك مع بيريجو على أي خدمة، فإنك توافق على الالتزام بهذه الشروط والأحكام وبأي عرض مشروع أو بيان أعمال نتفق عليه معك كتابةً. وإذا كنت لا توافق على ذلك، فيُرجى عدم استخدام الموقع أو خدماتنا.'],
            ],
            [
                'en' => ['Services We Provide', 'Perego is a creative studio offering video editing, motion graphics, graphic design, and web design and development. The exact deliverables, timeline, and fees for each engagement are defined in the proposal or statement of work agreed for that project; those project terms prevail over general descriptions on this site.'],
                'ar' => ['الخدمات التي نقدّمها', 'بيريجو استوديو إبداعي يقدّم خدمات مونتاج الفيديو والموشن جرافيك والتصميم الجرافيكي وتصميم وتطوير المواقع. وتُحدَّد المُخرجات والجدول الزمني والأتعاب لكل تعاقد في العرض أو بيان الأعمال المتفق عليه لذلك المشروع، وتُقدَّم شروط المشروع على الأوصاف العامة الواردة في هذا الموقع.'],
            ],
            [
                'en' => ['Client Responsibilities', 'You agree to provide accurate information, the materials and assets we need, and timely feedback and approvals so that work can proceed on schedule. You confirm that you own or are licensed to use any content you supply to us, and that our use of it will not infringe the rights of any third party.'],
                'ar' => ['مسؤوليات العميل', 'توافق على تزويدنا بمعلومات دقيقة وبالمواد والأصول التي نحتاجها، وبالملاحظات والموافقات في الوقت المناسب كي يسير العمل وفق الجدول. وتُقرّ بأنك تملك أو مُرخَّص لك باستخدام أي محتوى تزوّدنا به، وبأن استخدامنا له لن ينتهك حقوق أي طرف ثالث.'],
            ],
            [
                'en' => ['Intellectual Property', 'Unless a project agreement states otherwise, ownership of the final approved deliverables transfers to you once we have received full payment. Until then, all work remains our property. We retain the right to display completed work in our portfolio and marketing unless you ask us in writing not to. Pre-existing tools, source files, and any third-party or licensed assets remain owned by us or their respective licensors.'],
                'ar' => ['الملكية الفكرية', 'ما لم ينص اتفاق المشروع على خلاف ذلك، تنتقل ملكية المُخرجات النهائية المعتمدة إليك بمجرد استلامنا كامل المقابل المالي، وتظل جميع الأعمال ملكًا لنا حتى ذلك الحين. ونحتفظ بحق عرض الأعمال المنجزة ضمن معرض أعمالنا وموادنا التسويقية ما لم تطلب منّا خلاف ذلك كتابةً. أمّا الأدوات وملفات المصدر السابقة وأي أصول مملوكة لأطراف ثالثة أو مُرخَّصة فتبقى ملكًا لنا أو لأصحاب تراخيصها.'],
            ],
            [
                'en' => ['Payments &amp; Refunds', 'Fees, deposits, and the payment schedule are set out in your project agreement. Invoices are due within the period stated on them, and we may pause work on overdue accounts. Because our work is bespoke, amounts paid for work already performed are non-refundable; any refund of a deposit for work not yet started will be handled as set out in the project agreement.'],
                'ar' => ['المدفوعات والاستردادات', 'تُحدَّد الأتعاب والدفعات المقدّمة وجدول السداد في اتفاق مشروعك. وتُستحق الفواتير خلال المدة المبيّنة فيها، ويجوز لنا إيقاف العمل على الحسابات المتأخرة. ولأن أعمالنا مُصمَّمة خصيصًا، فإن المبالغ المدفوعة مقابل عمل تم إنجازه غير قابلة للاسترداد، ويُعالَج أي استرداد لدفعة مقدّمة عن عمل لم يبدأ بعد وفق ما ينص عليه اتفاق المشروع.'],
            ],
            [
                'en' => ['Limitation of Liability', 'Our services and this website are provided on a reasonable-effort basis. To the fullest extent permitted by law, Perego is not liable for indirect, incidental, or consequential losses, and our total liability arising from any engagement is limited to the fees you paid us for that engagement. Nothing in these Terms excludes liability that cannot be excluded by law.'],
                'ar' => ['حدود المسؤولية', 'تُقدَّم خدماتنا وهذا الموقع على أساس بذل العناية المعقولة. وإلى أقصى حد يسمح به القانون، لا تتحمّل بيريجو المسؤولية عن أي خسائر غير مباشرة أو عرضية أو تبعية، وتقتصر مسؤوليتنا الإجمالية الناشئة عن أي تعاقد على الأتعاب التي دفعتها لنا مقابله. ولا يستثني أيٌّ مما ورد في هذه الشروط أي مسؤولية لا يجوز استثناؤها قانونًا.'],
            ],
            [
                'en' => ['Changes to These Terms', 'We may update these Terms from time to time to reflect changes in our services or the law. The current version is always the one published on this page, with its effective date shown above. Your continued use of the site or our services after an update means you accept the revised Terms.'],
                'ar' => ['التعديلات على هذه الشروط', 'قد نُحدِّث هذه الشروط من حين لآخر لتعكس تغيّرات في خدماتنا أو في القانون. والنسخة السارية هي دائمًا المنشورة على هذه الصفحة بتاريخ سريانها المبيّن أعلاه. واستمرارك في استخدام الموقع أو خدماتنا بعد أي تحديث يعني قبولك للشروط المُعدَّلة.'],
            ],
            [
                'en' => ['Contact', 'Questions about these Terms are welcome. Please reach us at <strong>hello@perego.studio</strong> and we will be glad to help.'],
                'ar' => ['التواصل', 'نرحّب بأي استفسار حول هذه الشروط. يُرجى التواصل معنا عبر <strong>hello@perego.studio</strong> ويسعدنا مساعدتك.'],
            ],
        ],
    ],
    'privacy' => [
        'title' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
        'sections' => [
            [
                'en' => ['Information We Collect', 'We collect the information you give us directly — such as your name, email address, and the project details you share through our contact or brief forms — and a limited amount of technical information collected automatically when you browse, such as your device type, approximate location, and pages viewed.'],
                'ar' => ['المعلومات التي نجمعها', 'نجمع المعلومات التي تقدّمها إلينا مباشرةً — مثل اسمك وبريدك الإلكتروني وتفاصيل المشروع التي تشاركها عبر نماذج التواصل أو طلب العرض — إضافةً إلى قدر محدود من المعلومات التقنية التي تُجمع تلقائيًا أثناء تصفّحك، مثل نوع جهازك وموقعك التقريبي والصفحات التي تطّلع عليها.'],
            ],
            [
                'en' => ['How We Use Your Information', 'We use your information to respond to your enquiries, deliver and improve our services, keep our site secure, and — only if you have opted in — send you occasional updates. We process your data to perform our agreement with you, to meet legal obligations, and for our legitimate interest in running the studio.'],
                'ar' => ['كيف نستخدم معلوماتك', 'نستخدم معلوماتك للردّ على استفساراتك، وتقديم خدماتنا وتحسينها، والحفاظ على أمان موقعنا، وإرسال تحديثات من حين لآخر — فقط إذا اخترت الاشتراك. ونعالج بياناتك لتنفيذ اتفاقنا معك، والوفاء بالالتزامات القانونية، ولمصلحتنا المشروعة في إدارة الاستوديو.'],
            ],
            [
                'en' => ['Cookies &amp; Analytics', 'Our site uses essential cookies that make it work and privacy-respecting analytics that help us understand how it is used. You can control or block cookies through your browser settings; disabling some may affect how parts of the site function.'],
                'ar' => ['ملفات تعريف الارتباط والتحليلات', 'يستخدم موقعنا ملفات تعريف ارتباط أساسية تجعله يعمل، وأدوات تحليل تحترم الخصوصية تساعدنا على فهم كيفية استخدامه. ويمكنك التحكم في ملفات تعريف الارتباط أو حظرها من إعدادات متصفحك، مع العلم بأن تعطيل بعضها قد يؤثر في عمل أجزاء من الموقع.'],
            ],
            [
                'en' => ['Data Sharing', 'We do not sell your personal information. We share it only with trusted service providers who help us operate (for example hosting or email delivery) under confidentiality obligations, or where we are required to do so by law.'],
                'ar' => ['مشاركة البيانات', 'نحن لا نبيع معلوماتك الشخصية. ولا نشاركها إلا مع مزوّدي خدمات موثوقين يساعدوننا في التشغيل (مثل الاستضافة أو إرسال البريد) وبموجب التزامات سرية، أو حين يُلزمنا القانون بذلك.'],
            ],
            [
                'en' => ['Data Retention', 'We keep your information only for as long as we need it for the purposes described here or as required by law, and then we securely delete or anonymise it.'],
                'ar' => ['الاحتفاظ بالبيانات', 'نحتفظ بمعلوماتك فقط للمدة اللازمة للأغراض الموضّحة هنا أو وفقًا لما يقتضيه القانون، ثم نحذفها أو نجعلها مجهولة الهوية بطريقة آمنة.'],
            ],
            [
                'en' => ['Your Rights', 'Depending on where you live, you may have the right to access, correct, delete, or port your personal data, and to object to or restrict certain processing. To exercise any of these rights, contact us using the details below; you also have the right to complain to your local data-protection authority.'],
                'ar' => ['حقوقك', 'حسب مكان إقامتك، قد يكون لك الحق في الوصول إلى بياناتك الشخصية أو تصحيحها أو حذفها أو نقلها، وفي الاعتراض على بعض عمليات المعالجة أو تقييدها. ولممارسة أي من هذه الحقوق، تواصل معنا عبر البيانات أدناه، كما يحق لك تقديم شكوى إلى الجهة المختصة بحماية البيانات في بلدك.'],
            ],
            [
                'en' => ['Contact', 'For any privacy question or request, please email us at <strong>privacy@perego.studio</strong>.'],
                'ar' => ['التواصل', 'لأي استفسار أو طلب يتعلق بالخصوصية، يُرجى مراسلتنا عبر <strong>privacy@perego.studio</strong>.'],
            ],
        ],
    ],
];

/**
 * Build the block-editor body for a locale from the section list.
 *
 * @param list<array{en: array{0:string,1:string}, ar: array{0:string,1:string}}> $sections
 */
$buildBody = static function (array $sections, string $locale) use ($lead): string {
    $blocks = '<!-- wp:paragraph {"className":"legal__lead"} --><p class="legal__lead"><em>'
        . esc_html($lead[$locale]) . '</em></p><!-- /wp:paragraph -->' . "\n\n";

    foreach ($sections as $i => $section) {
        [$heading, $body] = $section[$locale];
        $num = $i + 1;
        $anchor = 's' . $num;
        $blocks .= '<!-- wp:heading {"anchor":"' . $anchor . '"} --><h2 class="wp-block-heading" id="' . $anchor . '">'
            . esc_html($num . '. ') . $heading . '</h2><!-- /wp:heading -->' . "\n";
        $blocks .= '<!-- wp:paragraph --><p>' . $body . '</p><!-- /wp:paragraph -->' . "\n\n";
    }

    return $blocks;
};

/**
 * Create the page if missing, else update its seed-owned body + curated date. `$existingId` lets the
 * caller pass an already-resolved page id (e.g. the AR translation from `pll_get_post`) so we never
 * rely on the slug, which Polylang de-duplicates per language (`terms` EN vs `terms-2` AR).
 */
$ensurePage = static function (string $slug, string $title, string $body, string $locale, int $existingId = 0) use ($pllReady, $lastUpdated): int {
    $existing = $existingId;
    if ($existing === 0) {
        $ids = get_posts([
            'post_type' => 'page',
            'name' => $slug,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
            'lang' => '',
            'suppress_filters' => true,
        ]);
        foreach ($ids as $id) {
            if (! $pllReady || pll_get_post_language((int) $id) === $locale) {
                $existing = (int) $id;
                break;
            }
        }
    }

    if ($existing !== 0) {
        wp_update_post(['ID' => $existing, 'post_title' => $title, 'post_content' => $body]);
        update_post_meta($existing, '_wp_page_template', 'legal');
        update_post_meta($existing, LegalUpdatedRenderer::META_UPDATED, $lastUpdated[$locale]);
        WP_CLI::log("Updated legal page ({$locale}): {$slug}");

        return $existing;
    }

    $postId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $body,
    ], true);

    if (is_wp_error($postId)) {
        WP_CLI::warning("Failed ({$locale}) {$slug}: " . $postId->get_error_message());

        return 0;
    }

    $postId = (int) $postId;
    update_post_meta($postId, '_wp_page_template', 'legal');
    update_post_meta($postId, LegalUpdatedRenderer::META_UPDATED, $lastUpdated[$locale]);

    if ($pllReady) {
        pll_set_post_language($postId, $locale);
    }

    WP_CLI::log("Created legal page ({$locale}): {$slug}");

    return $postId;
};

$done = 0;
$linked = 0;

foreach ($documents as $slug => $doc) {
    $enId = $ensurePage($slug, $doc['title']['en'], $buildBody($doc['sections'], 'en'), 'en');
    if ($enId === 0) {
        continue;
    }
    $done++;

    if (! $pllReady) {
        continue;
    }

    // Resolve the AR page via the Polylang translation link (not the slug — it's de-duplicated per
    // language) so we update the existing linked AR page instead of creating a duplicate.
    $existingAr = (int) pll_get_post($enId, 'ar');
    $arId = $ensurePage($slug, $doc['title']['ar'], $buildBody($doc['sections'], 'ar'), 'ar', $existingAr);
    if ($arId !== 0 && $existingAr === 0) {
        pll_save_post_translations(['en' => $enId, 'ar' => $arId]);
        $linked++;
    }
}

WP_CLI::success("Legal pages seeded — created/updated {$done}, EN/AR pairs linked {$linked}.");
