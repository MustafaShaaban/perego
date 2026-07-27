<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

/**
 * Renders Perego's six transactional emails (spec Phase 7/8) as the **exact** handoff HTML — the
 * branded 600px, table-based, inline-styled shell (gradient header + logo, dark card, violet summary
 * box, signature footer) plus each template's content — with a plain-text alternative. Ported 1:1
 * from `_design_handoff/.../emails/{en,ar}/*.html`.
 *
 * Pure PHP (no WordPress calls) so it unit-tests headless and renders identically wherever it runs:
 * merge values are escaped with `htmlspecialchars`, optional fields (phone/company/portfolio/cv) are
 * hidden when empty, and EN/AR is chosen by locale (AR flips the document to RTL). Email clients do
 * not support CSS custom properties, so — like CoreX's own mail Layout — the shell uses literal inline
 * colors; these are email-layout values, not theme design tokens.
 */
final class PeregoEmailRenderer
{
    /** Public signature address shown in every footer (handoff: the single professional mailbox). */
    public const CONTACT_EMAIL = 'info@peregoads.com';

    private const TEMPLATES = [
        'contact-confirmation',
        'project-brief-confirmation',
        'join-confirmation',
        'admin-notification',
        'comment-notification',
    ];

    public function __construct(
        private readonly string $siteUrl = 'https://peregoads.com',
        private readonly string $logoUrl = '',
    ) {
    }

    /**
     * @param array<string,string> $ctx merge values
     * @return array{subject:string,html:string,text:string}
     */
    public function render(string $template, string $locale, array $ctx): array
    {
        $locale = str_starts_with($locale, 'ar') ? 'ar' : 'en';

        if (! in_array($template, self::TEMPLATES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown Perego email template "%s".', $template));
        }

        $method = str_replace('-', '', ucwords($template, '-'));
        $method = lcfirst($method);

        /** @var array{subject:string,eyebrow:string,inner:string,text:string,minimalFooter?:bool} $parts */
        $parts = $this->{$method}($locale, $ctx);

        return [
            'subject' => $parts['subject'],
            'html' => $this->shell($locale, $parts['eyebrow'], $parts['subject'], $parts['inner'], (bool) ($parts['minimalFooter'] ?? false)),
            'text' => $this->textDoc($locale, $parts['text']),
        ];
    }

    // ---- Templates ---------------------------------------------------------

    /** @param array<string,string> $ctx @return array<string,string> */
    private function contactConfirmation(string $locale, array $ctx): array
    {
        $ar = $locale === 'ar';
        $name = $this->esc($ctx['name'] ?? '');
        $email = $this->esc($ctx['email'] ?? '');
        $message = $this->esc($ctx['message'] ?? '');

        $subject = $ar ? 'شكرًا لتواصلك — بيريجو' : 'Thanks for reaching out — Perego';
        $heading = $ar ? "شكرًا لك، {$name}!" : "Thank you, {$name}!";
        $intro = $ar
            ? 'لقد استلمنا رسالتك وسيعاود أحد أعضاء فريقنا التواصل معك قريبًا. وفي هذه الأثناء، إليك نسخة مما أرسلته إلينا.'
            : "We've received your message and a member of our team will get back to you shortly. In the meantime, here's a copy of what you sent us.";
        $msgLabel = $ar ? 'رسالتك' : 'Your message';
        $replyLabel = $ar ? 'الرد على' : 'Reply-to';
        $cta = $ar ? 'استعرض أعمالنا' : 'Visit our work';

        $summary = $this->summaryBox(
            $this->eyebrowLine($msgLabel)
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#ffffff;">' . $message . '</p>'
            . '<p style="margin:0;font-size:13px;color:rgba(255,255,255,0.6);">' . $replyLabel . ': ' . $email . '</p>'
        );

        $inner = $this->checkHeading($heading, $intro) . $summary . $this->ctaRow($this->siteUrl . '/work', $cta);

        $text = $this->stripTitle($heading) . "\n\n" . $intro . "\n\n"
            . $msgLabel . ": " . ($ctx['message'] ?? '') . "\n" . $replyLabel . ": " . ($ctx['email'] ?? '')
            . "\n\n" . $cta . ": " . $this->siteUrl . '/work';

        return ['subject' => $subject, 'eyebrow' => $ar ? 'استوديو إبداعي' : 'Creative Studio', 'inner' => $inner, 'text' => $text];
    }

    /** @param array<string,string> $ctx @return array<string,string> */
    private function projectBriefConfirmation(string $locale, array $ctx): array
    {
        $ar = $locale === 'ar';
        $name = $this->esc($ctx['name'] ?? '');

        $subject = $ar ? 'استلمنا ملخص مشروعك — بيريجو' : 'We got your project brief — Perego';
        $heading = $ar ? "استلمنا ملخصك، {$name}!" : "Your brief is in, {$name}!";
        $intro = $ar
            ? 'شكرًا لإخبارنا عن مشروعك. سيراجع فريقنا التفاصيل أدناه ويعاود التواصل معك خلال يوم إلى يومَي عمل بالخطوات التالية.'
            : 'Thanks for telling us about your project. Our team will review the details below and get back to you within 1–2 business days with next steps.';
        $lbl = $ar
            ? ['brief' => 'ملخص المشروع', 'services' => 'الخدمة/الخدمات', 'budget' => 'الميزانية', 'subject' => 'الموضوع', 'message' => 'الرسالة', 'reply' => 'الرد على']
            : ['brief' => 'Project brief', 'services' => 'Service(s)', 'budget' => 'Budget', 'subject' => 'Subject', 'message' => 'Message', 'reply' => 'Reply-to'];
        $cta = $ar ? 'شاهد أحدث أعمالنا' : 'See our recent work';

        $rows = $this->kvRow($lbl['services'], $this->esc($ctx['services'] ?? ''))
            . $this->kvRow($lbl['budget'], $this->esc($ctx['budget'] ?? ''))
            . $this->kvRow($lbl['subject'], $this->esc($ctx['subject'] ?? ''));

        // Optional phone/company are appended to the reply-to line only when present.
        $replyParts = array_filter([
            $this->esc($ctx['email'] ?? ''),
            $this->esc($ctx['phone'] ?? ''),
            $this->esc($ctx['company'] ?? ''),
        ], static fn (string $v): bool => $v !== '');

        $summary = $this->summaryBox(
            $this->eyebrowLine($lbl['brief'])
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;color:#ffffff;line-height:1.5;">' . $rows . '</table>'
            . $this->eyebrowLine($lbl['message'], '14px 0 6px')
            . '<p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#ffffff;">' . $this->esc($ctx['message'] ?? '') . '</p>'
            . '<p style="margin:0;font-size:13px;color:rgba(255,255,255,0.6);">' . $lbl['reply'] . ': ' . implode(' &nbsp;•&nbsp; ', $replyParts) . '</p>'
        );

        $inner = $this->checkHeading($heading, $intro) . $summary . $this->ctaRow($this->siteUrl . '/work', $cta);

        $text = $this->stripTitle($heading) . "\n\n" . $intro . "\n\n"
            . $lbl['services'] . ": " . ($ctx['services'] ?? '') . "\n"
            . $lbl['budget'] . ": " . ($ctx['budget'] ?? '') . "\n"
            . $lbl['subject'] . ": " . ($ctx['subject'] ?? '') . "\n"
            . $lbl['message'] . ": " . ($ctx['message'] ?? '') . "\n"
            . $lbl['reply'] . ": " . implode(' • ', array_map($this->stripTitle(...), $replyParts))
            . "\n\n" . $cta . ": " . $this->siteUrl . '/work';

        return ['subject' => $subject, 'eyebrow' => $ar ? 'ابدأ مشروعك' : 'Start a Project', 'inner' => $inner, 'text' => $text];
    }

    /** @param array<string,string> $ctx @return array<string,string> */
    private function joinConfirmation(string $locale, array $ctx): array
    {
        $ar = $locale === 'ar';
        $name = $this->esc($ctx['name'] ?? '');
        // Only a valid http(s) portfolio URL is shown — anything else (empty, javascript:, …) hides
        // the line rather than printing a dead or unsafe link.
        $portfolio = $this->escUrl($ctx['portfolio'] ?? '');
        $cv = $this->esc($ctx['cv_filename'] ?? '') ?: '—';

        $subject = $ar ? 'استلمنا طلبك — بيريجو' : 'We received your application — Perego';
        $heading = $ar ? "استلمنا طلبك، {$name}!" : "Application received, {$name}!";
        $intro = $ar
            ? 'شكرًا لاهتمامك بالنمو مع بيريجو. لقد استلمنا بياناتك وسيراجع فريقنا أعمالك. وإن كان هناك تناسب، سنتواصل معك للخطوة التالية.'
            : "Thank you for your interest in growing with Perego. We've received your details and our team will review your portfolio. If there's a fit, we'll reach out to take the next step.";
        $lbl = $ar ? ['sum' => 'ملخص الطلب', 'pf' => 'ملف الأعمال', 'cv' => 'السيرة الذاتية'] : ['sum' => 'Submission summary', 'pf' => 'Portfolio', 'cv' => 'CV'];
        $cta = $ar ? 'تعرّف على ما نقدّمه' : 'See what we do';

        $pfLine = $portfolio !== ''
            ? '<p style="margin:0 0 8px;font-size:15px;color:#ffffff;">' . $lbl['pf'] . ': <a href="' . $portfolio . '" style="color:#e59bf7;">' . $portfolio . '</a></p>'
            : '';

        $summary = $this->summaryBox(
            $this->eyebrowLine($lbl['sum'])
            . $pfLine
            . '<p style="margin:0;font-size:15px;color:#ffffff;">' . $lbl['cv'] . ': ' . $cv . '</p>'
        );

        $inner = $this->checkHeading($heading, $intro) . $summary . $this->ctaRow($this->siteUrl . '/services', $cta);

        $text = $this->stripTitle($heading) . "\n\n" . $intro . "\n\n"
            . ($portfolio !== '' ? $lbl['pf'] . ": " . $portfolio . "\n" : '')
            . $lbl['cv'] . ": " . ($ctx['cv_filename'] ?? '—')
            . "\n\n" . $cta . ": " . $this->siteUrl . '/services';

        return ['subject' => $subject, 'eyebrow' => $ar ? 'انضم إلى الفريق' : 'Join the team', 'inner' => $inner, 'text' => $text];
    }

    /** @param array<string,string> $ctx @return array<string,string> */
    private function adminNotification(string $locale, array $ctx): array
    {
        $ar = $locale === 'ar';
        $formName = $this->esc($ctx['form_name'] ?? '');
        $submittedAt = $this->esc($ctx['submitted_at'] ?? '');
        $reply = $this->esc($ctx['reply_email'] ?? '');
        $fieldsHtml = (string) ($ctx['fields_html'] ?? ''); // pre-rendered, trusted <tr> rows

        $subject = ($ar ? "طلب {$formName} جديد — بيريجو" : "New {$formName} submission — Perego");
        $heading = $ar ? "طلب {$formName} جديد" : "New {$formName} submission";
        $received = $ar ? "تم الاستلام {$submittedAt} عبر الموقع." : "Received {$submittedAt} via the website.";
        $cta = $ar ? 'الرد على المُرسِل' : 'Reply to sender';

        $inner = '<tr><td style="padding:34px 36px 6px;">'
            . '<h1 style="margin:0 0 6px;font-size:22px;font-weight:700;color:#ffffff;">' . $heading . '</h1>'
            . '<p style="margin:0 0 20px;font-size:13px;color:rgba(255,255,255,0.55);">' . $received . '</p></td></tr>'
            . '<tr><td style="padding:0 36px 14px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:rgba(72,3,131,0.35);border:1px solid rgba(216,106,243,0.25);border-radius:14px;">'
            . '<tr><td style="padding:18px 22px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;color:#ffffff;line-height:1.5;">'
            . $fieldsHtml . '</table></td></tr></table></td></tr>'
            . '<tr><td style="padding:16px 36px 36px;"><a href="mailto:' . $this->escUrl($reply, true) . '" style="display:inline-block;background:#d86af3;color:#2a0148;text-decoration:none;font-weight:700;font-size:15px;padding:13px 30px;border-radius:9px;">' . $cta . '</a></td></tr>';

        $text = $this->stripTitle($heading) . "\n" . $received . "\n\n" . strip_tags(str_replace(['</tr>', '</td><td'], ["\n", ': '], $fieldsHtml))
            . "\n" . $cta . ": " . ($ctx['reply_email'] ?? '');

        return ['subject' => $subject, 'eyebrow' => $ar ? 'طلب جديد' : 'New submission', 'inner' => $inner, 'text' => $text, 'minimalFooter' => true];
    }

    /** @param array<string,string> $ctx @return array<string,string> */
    private function commentNotification(string $locale, array $ctx): array
    {
        $ar = $locale === 'ar';
        $postTitle = $this->esc($ctx['post_title'] ?? '');
        $postUrl = $this->escUrl($ctx['post_url'] ?? '');
        $submittedAt = $this->esc($ctx['submitted_at'] ?? '');
        $cName = $this->esc($ctx['commenter_name'] ?? '');
        $cEmail = $this->esc($ctx['commenter_email'] ?? '');
        $body = $this->esc($ctx['comment_body'] ?? '');
        $approve = $this->escUrl($ctx['approve_url'] ?? '');

        $subject = $ar ? 'تعليق جديد بانتظار المراجعة — بيريجو' : 'New comment awaiting review — Perego';
        $heading = $ar ? 'تعليق جديد بانتظار المراجعة' : 'New comment awaiting review';
        $onLabel = $ar ? 'على' : 'On';
        $approveCta = $ar ? 'مراجعة وموافقة' : 'Review &amp; approve';
        $replyCta = $ar ? 'الرد بالبريد' : 'Reply by email';

        $inner = '<tr><td style="padding:34px 36px 6px;">'
            . '<h1 style="margin:0 0 6px;font-size:22px;font-weight:700;color:#ffffff;">' . $heading . '</h1>'
            . '<p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:rgba(255,255,255,0.6);">' . $onLabel . ' <a href="' . $postUrl . '" style="color:#e59bf7;">' . $postTitle . '</a> &nbsp;•&nbsp; ' . $submittedAt . '</p></td></tr>'
            . '<tr><td style="padding:0 36px 14px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:rgba(72,3,131,0.35);border:1px solid rgba(216,106,243,0.25);border-radius:14px;"><tr><td style="padding:20px 22px;">'
            . '<p style="margin:0 0 4px;font-size:15px;font-weight:700;color:#ffffff;">' . $cName . '</p>'
            . '<p style="margin:0 0 14px;font-size:13px;color:rgba(255,255,255,0.6);">' . $cEmail . '</p>'
            . '<p style="margin:0;font-size:15px;line-height:1.6;color:#ffffff;border-left:3px solid #d86af3;padding-left:14px;">' . $body . '</p>'
            . '</td></tr></table></td></tr>'
            . '<tr><td style="padding:16px 36px 36px;">'
            . '<a href="' . $approve . '" style="display:inline-block;background:#d86af3;color:#2a0148;text-decoration:none;font-weight:700;font-size:15px;padding:13px 30px;border-radius:9px;margin-right:10px;">' . $approveCta . '</a>'
            . '<a href="mailto:' . $this->escUrl($ctx['commenter_email'] ?? '', true) . '" style="display:inline-block;background:transparent;border:1px solid rgba(216,106,243,0.5);color:#e59bf7;text-decoration:none;font-weight:700;font-size:15px;padding:12px 26px;border-radius:9px;">' . $replyCta . '</a></td></tr>';

        $text = $this->stripTitle($heading) . "\n" . $onLabel . ' ' . $this->stripTitle($postTitle) . ' • ' . $this->stripTitle($submittedAt) . "\n\n"
            . $this->stripTitle($cName) . ' <' . ($ctx['commenter_email'] ?? '') . ">\n" . ($ctx['comment_body'] ?? '')
            . "\n\n" . $this->stripTitle($approveCta) . ': ' . ($ctx['approve_url'] ?? '');

        return ['subject' => $subject, 'eyebrow' => $ar ? 'تعليق جديد' : 'New comment', 'inner' => $inner, 'text' => $text, 'minimalFooter' => true];
    }

    // ---- Shared shell ------------------------------------------------------

    private function shell(string $locale, string $eyebrow, string $subject, string $innerRows, bool $minimalFooter): string
    {
        $dir = $locale === 'ar' ? 'rtl' : 'ltr';
        $lang = $locale === 'ar' ? 'ar' : 'en';

        return '<!DOCTYPE html><html lang="' . $lang . '" dir="' . $dir . '"><head><meta charset="UTF-8" />'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0" /><meta name="color-scheme" content="light dark" />'
            . '<title>' . $subject . '</title></head>'
            . '<body style="margin:0;padding:0;background:#0c0020;font-family:\'Open Sans\',Arial,Helvetica,sans-serif;">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $subject . '</div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0c0020;padding:32px 12px;"><tr><td align="center">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#160435;border-radius:20px;overflow:hidden;border:1px solid rgba(216,106,243,0.28);">'
            . $this->header($eyebrow)
            . $innerRows
            . $this->footer($minimalFooter)
            . '</table></td></tr></table></body></html>';
    }

    private function header(string $eyebrow): string
    {
        $brand = $this->logoUrl !== ''
            ? '<img src="' . $this->escUrl($this->logoUrl) . '" alt="Perego" width="140" height="43" style="display:block;border:0;height:auto;" />'
            : '<strong style="color:#ffffff;font-size:22px;">Perego</strong>';

        return '<tr><td style="background:linear-gradient(120deg,#4a0d8f,#6c1eae 60%,#7d24c4);padding:30px 36px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td>' . $brand . '</td>'
            . '<td align="right" style="font-size:12px;color:rgba(255,255,255,0.85);text-transform:uppercase;letter-spacing:0.14em;">' . $this->esc($eyebrow) . '</td>'
            . '</tr></table></td></tr>';
    }

    private function footer(bool $minimal): string
    {
        if ($minimal) {
            return '<tr><td style="background:#0f0126;padding:22px 36px;border-top:1px solid rgba(255,255,255,0.08);">'
                . '<p style="margin:0;font-size:12px;color:rgba(255,255,255,0.45);">Automated notification from the Perego website. Do not share externally.</p></td></tr>';
        }

        return '<tr><td style="background:#0f0126;padding:26px 36px;border-top:1px solid rgba(255,255,255,0.08);">'
            . '<p style="margin:0 0 6px;font-size:13px;color:rgba(255,255,255,0.7);">Best regards,<br /><strong style="color:#ffffff;">The Perego Team</strong> &nbsp;•&nbsp; <a href="mailto:' . self::CONTACT_EMAIL . '" style="color:#e59bf7;text-decoration:none;">' . self::CONTACT_EMAIL . '</a></p>'
            . '<p style="margin:0;font-size:12px;color:rgba(255,255,255,0.45);">© Perego Creative Studio. Serving creators across the Arab region.</p></td></tr>';
    }

    // ---- Content helpers ---------------------------------------------------

    private function checkHeading(string $heading, string $intro): string
    {
        return '<tr><td style="padding:40px 36px 8px;">'
            . '<div style="display:inline-block;width:54px;height:54px;border-radius:50%;background:rgba(216,106,243,0.16);text-align:center;line-height:54px;font-size:26px;color:#d86af3;">&#10003;</div>'
            . '<h1 style="margin:22px 0 10px;font-size:26px;font-weight:700;color:#ffffff;">' . $heading . '</h1>'
            . '<p style="margin:0 0 18px;font-size:16px;line-height:1.6;color:rgba(255,255,255,0.82);">' . $intro . '</p></td></tr>';
    }

    private function summaryBox(string $inner): string
    {
        return '<tr><td style="padding:8px 36px 12px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:rgba(72,3,131,0.35);border:1px solid rgba(216,106,243,0.25);border-radius:14px;">'
            . '<tr><td style="padding:20px 22px;">' . $inner . '</td></tr></table></td></tr>';
    }

    private function eyebrowLine(string $label, string $margin = '0 0 6px'): string
    {
        return '<p style="margin:' . $margin . ';font-size:12px;text-transform:uppercase;letter-spacing:0.14em;color:#d86af3;font-weight:700;">' . $label . '</p>';
    }

    private function kvRow(string $label, string $value): string
    {
        return '<tr><td style="padding:4px 0;color:rgba(255,255,255,0.6);width:120px;">' . $label . '</td><td style="padding:4px 0;">' . $value . '</td></tr>';
    }

    private function ctaRow(string $url, string $label): string
    {
        return '<tr><td style="padding:22px 36px 36px;"><a href="' . $this->escUrl($url) . '" style="display:inline-block;background:#d86af3;color:#2a0148;text-decoration:none;font-weight:700;font-size:15px;padding:14px 32px;border-radius:9px;">' . $label . '</a></td></tr>';
    }

    private function textDoc(string $locale, string $body): string
    {
        $sig = $locale === 'ar' ? "مع أطيب التحيات،\nفريق Perego" : "Best regards,\nThe Perego Team";

        return $body . "\n\n" . $sig . " • " . self::CONTACT_EMAIL;
    }

    // ---- Escaping (pure; no WordPress) -------------------------------------

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /** Escape a URL for an attribute; drops anything not http(s)/mailto to avoid javascript: etc. */
    private function escUrl(string $url, bool $mailto = false): string
    {
        $url = trim($url);
        if ($mailto) {
            return filter_var($url, FILTER_VALIDATE_EMAIL) ? $this->esc($url) : '';
        }
        if ($url !== '' && ! preg_match('#^https?://#i', $url)) {
            $url = '';
        }

        return $this->esc($url);
    }

    private function stripTitle(string $htmlEscaped): string
    {
        return html_entity_decode(strip_tags($htmlEscaped), ENT_QUOTES, 'UTF-8');
    }
}
