<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Content;

defined('ABSPATH') || exit;

/**
 * Global editorial strings that appear on language-neutral FSE templates (404, search, legal chrome)
 * and therefore must be resolved per language at render time (Polylang Free cannot translate template
 * parts). Mirrors the handoff `content/{en,ar}.json` (notFound / search / legal). Falls back to `en`.
 *
 * This is the interim home for these strings; the prompt's `perego_global_section` CPT is the eventual
 * editor-canvas source (tracked in PROGRESS "Next"). Kept as a provider for now, consistent with the
 * other Perego content providers.
 */
final class GlobalContent
{
    private const DEFAULT_LOCALE = 'en';

    /** @var array<string, array<string, mixed>> */
    private const COPY = [
        'en' => [
            'notFound' => [
                'code' => '404',
                'title' => 'This page took a creative detour',
                'text' => "The page you're looking for doesn't exist or has been moved. Let's get you back to something worth watching.",
                'backHome' => 'Back to Home',
                'contact' => 'Contact Us',
            ],
            'search' => [
                'h1' => 'Search results',
                'resultsFor' => 'Showing results for',
                'matchesFound' => 'matches found',
                'placeholder' => 'Search the site…',
                'button' => 'Search',
                'empty' => 'No results found. Try a different search term.',
                'emptyHint' => 'Check your spelling or use more general keywords.',
            ],
            'legal' => [
                'lastUpdated' => 'Last updated',
                'tocTitle' => 'On this page',
                'reviewNote' => 'This is a draft for design/layout purposes and must be reviewed by legal counsel before publication.',
            ],
            'journal' => [
                'h1' => 'The Perego Journal',
                'lead' => 'Notes on video, motion, design and the web from the studio floor.',
                'empty' => 'No articles published yet. Check back soon.',
                'minRead' => '%d min read',
                'relatedArticles' => 'Related articles',
                'commentsTitle' => '%d Comments',
                'commentsTitleOne' => '%d Comment',
                'reply' => 'Reply',
                'replyTo' => 'Reply to %s',
                'leaveComment' => 'Leave a comment',
                'replyingTo' => 'Replying to',
                'cancel' => 'Cancel',
                'nameLabel' => 'Name',
                'namePlaceholder' => 'Your name',
                'emailLabel' => 'E-mail',
                'emailNote' => '(not published)',
                'emailPlaceholder' => 'you@email.com',
                'commentLabel' => 'Comment',
                'commentPlaceholder' => 'Share your thoughts…',
                'submit' => 'Post comment',
                'hpLabel' => 'Leave this field empty',
                'commentStatus' => [
                    'sending' => 'Sending…',
                    'approved' => 'Thanks! Your comment has been posted.',
                    'moderation' => 'Thanks! Your comment is awaiting review.',
                    'invalid' => 'Please fill in your name, a valid e-mail, and a comment.',
                    'spam' => 'Your comment could not be submitted.',
                    'closed' => 'Comments are closed for this post.',
                    'rate_limit' => 'You are commenting too quickly — please wait a moment and try again.',
                    'flood' => 'You are commenting too quickly — please wait a moment and try again.',
                    'duplicate' => 'It looks like you already said that.',
                    'session' => 'Your session expired — please refresh the page and try again.',
                    'server_error' => 'Something went wrong. Please try again shortly.',
                ],
            ],
            'footer' => [
                'blurb' => 'We would be delighted to hear from you to provide creative technical solutions, assistance, and tailored recommendations that best suit your needs.',
            ],
            'join' => [
                'name' => 'Full name',
                'namePlaceholder' => 'Put your name here',
                'email' => 'Email',
                'emailPlaceholder' => 'Put your Email here',
                'portfolio' => 'Portfolio/website link',
                'portfolioPlaceholder' => 'Put your Portfolio/website link',
                'cv' => 'CV',
                'cvHint' => 'Upload your CV here',
                'submit' => 'Apply now',
                'uploading' => 'Uploading your CV…',
                'submitting' => 'Sending your application…',
                'success' => 'Thanks! Your application is in — we\'ll be in touch if there\'s a fit.',
                'nameRequired' => 'Please enter your name.',
                'emailInvalid' => 'Please enter a valid email address.',
                'cvRequired' => 'Please attach your CV.',
                'formHasErrors' => 'Please fix the highlighted fields and try again.',
                'wrong_type' => 'Please attach a PDF, DOC or DOCX file.',
                'too_large' => 'That file is over 10 MB. Please attach a smaller CV.',
                'rate_limit' => 'Too many attempts — please try again in a few minutes.',
                'server_error' => 'Something went wrong on our end. Please try again shortly.',
            ],
            'lightbox' => [
                'close' => 'Close',
                'prev' => 'Previous',
                'next' => 'Next',
            ],
        ],
        'ar' => [
            'notFound' => [
                'code' => '404',
                'title' => 'هذه الصفحة سلكت منعطفًا إبداعيًا',
                'text' => 'الصفحة التي تبحث عنها غير موجودة أو تم نقلها. لنعُد بك إلى ما يستحق المشاهدة.',
                'backHome' => 'العودة إلى الرئيسية',
                'contact' => 'تواصل معنا',
            ],
            'search' => [
                'h1' => 'نتائج البحث',
                'resultsFor' => 'عرض نتائج عن',
                'matchesFound' => 'نتيجة مطابقة',
                'placeholder' => 'ابحث في الموقع…',
                'button' => 'بحث',
                'empty' => 'لا توجد نتائج. جرّب كلمة بحث مختلفة.',
                'emptyHint' => 'تحقق من الإملاء أو استخدم كلمات أعمّ.',
            ],
            'legal' => [
                'lastUpdated' => 'آخر تحديث',
                'tocTitle' => 'في هذه الصفحة',
                'reviewNote' => 'هذه مسودة لأغراض التصميم والتنسيق، ويجب مراجعتها من قبل مستشار قانوني قبل النشر.',
            ],
            'journal' => [
                'h1' => 'مدونة بيريجو',
                'lead' => 'ملاحظات في الفيديو والموشن والتصميم والويب من داخل الاستوديو.',
                'empty' => 'لا توجد مقالات منشورة بعد. عُد قريبًا.',
                'minRead' => '%d دقيقة للقراءة',
                'relatedArticles' => 'مقالات ذات صلة',
                'commentsTitle' => '%d تعليقات',
                'commentsTitleOne' => 'تعليق واحد',
                'reply' => 'رد',
                'replyTo' => 'الرد على %s',
                'leaveComment' => 'اترك تعليقًا',
                'replyingTo' => 'الرد على',
                'cancel' => 'إلغاء',
                'nameLabel' => 'الاسم',
                'namePlaceholder' => 'اسمك',
                'emailLabel' => 'البريد الإلكتروني',
                'emailNote' => '(لن يُنشر)',
                'emailPlaceholder' => 'you@email.com',
                'commentLabel' => 'التعليق',
                'commentPlaceholder' => 'شاركنا رأيك…',
                'submit' => 'أرسل التعليق',
                'hpLabel' => 'اترك هذا الحقل فارغًا',
                'commentStatus' => [
                    'sending' => 'جارٍ الإرسال…',
                    'approved' => 'شكرًا! تم نشر تعليقك.',
                    'moderation' => 'شكرًا! تعليقك قيد المراجعة.',
                    'invalid' => 'يرجى إدخال اسمك وبريد إلكتروني صحيح ونص التعليق.',
                    'spam' => 'تعذّر إرسال تعليقك.',
                    'closed' => 'التعليقات مغلقة على هذه المقالة.',
                    'rate_limit' => 'أنت تعلّق بسرعة كبيرة — يرجى الانتظار قليلًا والمحاولة مرة أخرى.',
                    'flood' => 'أنت تعلّق بسرعة كبيرة — يرجى الانتظار قليلًا والمحاولة مرة أخرى.',
                    'duplicate' => 'يبدو أنك قلت ذلك بالفعل.',
                    'session' => 'انتهت جلستك — يرجى تحديث الصفحة والمحاولة مرة أخرى.',
                    'server_error' => 'حدث خطأ ما. يرجى المحاولة بعد قليل.',
                ],
            ],
            'footer' => [
                'blurb' => 'يسعدنا التواصل معك لتقديم حلول تقنية إبداعية والمساعدة والتوصيات المخصصة التي تناسب احتياجاتك على أفضل وجه.',
            ],
            'join' => [
                'name' => 'الاسم الكامل',
                'namePlaceholder' => 'أدخل اسمك هنا',
                'email' => 'البريد الإلكتروني',
                'emailPlaceholder' => 'أدخل بريدك الإلكتروني هنا',
                'portfolio' => 'رابط ملف الأعمال / الموقع',
                'portfolioPlaceholder' => 'أدخل رابط ملف أعمالك أو موقعك',
                'cv' => 'السيرة الذاتية',
                'cvHint' => 'ارفع سيرتك الذاتية هنا',
                'submit' => 'قدّم الآن',
                'uploading' => 'جارٍ رفع سيرتك الذاتية…',
                'submitting' => 'جارٍ إرسال طلبك…',
                'success' => 'شكرًا! تم استلام طلبك — سنتواصل معك إن كان هناك تناسب.',
                'nameRequired' => 'يرجى إدخال اسمك.',
                'emailInvalid' => 'يرجى إدخال بريد إلكتروني صحيح.',
                'cvRequired' => 'يرجى إرفاق سيرتك الذاتية.',
                'formHasErrors' => 'يرجى تصحيح الحقول المظللة والمحاولة مرة أخرى.',
                'wrong_type' => 'يرجى إرفاق ملف PDF أو DOC أو DOCX.',
                'too_large' => 'حجم الملف يتجاوز 10 ميجابايت. يرجى إرفاق سيرة أصغر.',
                'rate_limit' => 'محاولات كثيرة — يرجى المحاولة بعد بضع دقائق.',
                'server_error' => 'حدث خطأ لدينا. يرجى المحاولة مرة أخرى بعد قليل.',
            ],
            'lightbox' => [
                'close' => 'إغلاق',
                'prev' => 'السابق',
                'next' => 'التالي',
            ],
        ],
    ];

    /** @return array<string, string> */
    public function journal(): array
    {
        return self::COPY[$this->locale]['journal'];
    }

    /** The localized "Home" breadcrumb root label (handoff `ui.breadcrumbHome`). */
    public function uiHome(): string
    {
        return $this->locale === 'ar' ? 'الرئيسية' : 'Home';
    }

    /**
     * The localized single-post reading estimate, e.g. "6 min read" / "٦ دقيقة للقراءة" (handoff
     * `readTime`). The caller supplies the already-computed minute count.
     */
    public function readTime(int $minutes): string
    {
        return sprintf(self::COPY[$this->locale]['journal']['minRead'], $minutes);
    }

    private readonly string $locale;

    public function __construct(string $locale = self::DEFAULT_LOCALE)
    {
        $this->locale = isset(self::COPY[$locale]) ? $locale : self::DEFAULT_LOCALE;
    }

    /** @return array<string, string> */
    public function notFound(): array
    {
        return self::COPY[$this->locale]['notFound'];
    }

    /** @return array<string, string> */
    public function search(): array
    {
        return self::COPY[$this->locale]['search'];
    }

    /** @return array<string, string> */
    public function legal(): array
    {
        return self::COPY[$this->locale]['legal'];
    }

    /** @return array<string, string> */
    public function join(): array
    {
        return self::COPY[$this->locale]['join'];
    }

    /** @return array{close: string, prev: string, next: string} */
    public function lightbox(): array
    {
        return self::COPY[$this->locale]['lightbox'];
    }

    /** @return array<string, string> */
    public function footer(): array
    {
        return self::COPY[$this->locale]['footer'];
    }
}
