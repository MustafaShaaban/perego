<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

/**
 * Renders Perego's transactional emails (spec Phase 7/8) as the **exact** handoff HTML — the branded
 * 600px, table-based, inline-styled shell (gradient header + logo, dark card, violet summary box,
 * signature footer) plus each template's content — with a plain-text alternative. The five original
 * templates are ported 1:1 from `_design_handoff/.../emails/{en,ar}/*.html`; `reply` is new (it has
 * no handoff counterpart) and reuses that same shell so an operator's answer looks like the rest.
 *
 * Pure PHP (no WordPress calls) so it unit-tests headless and renders identically wherever it runs:
 * merge values are escaped with `htmlspecialchars`, optional fields (phone/company/portfolio/cv) are
 * hidden when empty, and EN/AR is chosen by locale (AR flips the document to RTL). Email clients do
 * not support CSS custom properties, so — like CoreX's own mail Layout — the shell uses literal inline
 * colors, named as constants below; these are email-layout values, not theme design tokens.
 */
final class PeregoEmailRenderer
{
    /** Public signature address shown in every footer (handoff: the single professional mailbox). */
    public const CONTACT_EMAIL = 'info@peregoads.com';

    /*
     * Opaque colours, and why every one of them is a flat hex.
     *
     * Outlook renders with the Word engine, which does two things this design did not survive:
     * it drops `rgba()` outright, and it does not inherit `color` from a <table> into a <td>. The
     * shell used `rgba()` for eleven text colours and five backgrounds, and let value cells inherit
     * white from an ancestor table — so on those clients the text fell back to the client default
     * (black) while the dark card behind it also disappeared. The client's report was simply that
     * the notification email was unreadable (2026-07-27).
     *
     * Each constant below is the `rgba()` it replaces, already flattened against the surface it sits
     * on, so the rendered colour is unchanged where the old CSS worked. Anything that shows text now
     * states its own colour, and anything that provides contrast also carries a `bgcolor` attribute.
     */

    private const PAGE_BG = '#0c0020';
    private const CARD_BG = '#160435';
    private const CARD_BORDER = '#451e5b';   // was rgba(216,106,243,0.28) on PAGE_BG
    private const PANEL_BG = '#280450';      // was rgba(72,3,131,0.35)   on CARD_BG
    private const PANEL_BORDER = '#471e65';  // was rgba(216,106,243,0.25) on CARD_BG
    private const FOOTER_BG = '#0f0126';
    private const FOOTER_RULE = '#221537';   // was rgba(255,255,255,0.08) on FOOTER_BG
    private const HEADER_BG = '#6c1eae';     // the gradient's mid stop, as its solid fallback
    private const CHECK_BG = '#351453';      // was rgba(216,106,243,0.16) on CARD_BG

    private const INK = '#ffffff';
    private const INK_SOFT = '#d5d2db';      // was rgba(255,255,255,0.82) on CARD_BG
    private const INK_MUTED = '#a99bb9';     // was rgba(255,255,255,0.6)  on PANEL_BG
    private const INK_SUBTLE = '#968ea4';    // was rgba(255,255,255,0.55) on CARD_BG
    private const INK_FOOTER = '#b7b3be';    // was rgba(255,255,255,0.7)  on FOOTER_BG
    private const INK_FOOTER_FAINT = '#7b7388'; // was rgba(255,255,255,0.45) on FOOTER_BG
    private const INK_HEADER = '#e9ddf3';    // was rgba(255,255,255,0.85) on HEADER_BG

    private const LINK = '#e59bf7';
    private const ACCENT = '#d86af3';
    private const ACCENT_INK = '#2a0148';    // button label; only ever on an ACCENT field

    private const TEMPLATES = [
        'contact-confirmation',
        'project-brief-confirmation',
        'join-confirmation',
        'admin-notification',
        'comment-notification',
        'reply',
    ];

    public function __construct(
        private readonly string $siteUrl = 'https://peregoads.com',
        private readonly string $logoUrl = '',
    ) {
    }

    /**
     * Build the `fields_html` payload for the admin-notification template — one label/value row per
     * entry, in the same shape as kvRow(). Shared by every form that notifies the team, so no caller
     * hand-rolls email markup or its escaping.
     *
     * A value may be a plain string, or `['text' => …, 'url' => …]` to render the cell as a link (the
     * careers CV download). Only http(s) survives; anything else degrades to the text alone rather than
     * emitting a `javascript:` href.
     *
     * @param array<string, string|array{text?:string,url?:string}> $fields
     */
    public static function fieldRows(array $fields): string
    {
        $escape = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');

        $rows = '';
        foreach ($fields as $label => $value) {
            if (is_array($value)) {
                $text = $escape((string) ($value['text'] ?? ''));
                $url = trim((string) ($value['url'] ?? ''));
                $cell = preg_match('#^https?://#i', $url) === 1
                    ? '<a href="' . $escape($url) . '" style="color:' . self::LINK . ';">' . $text . '</a>'
                    : $text;
            } else {
                $cell = $escape($value);
            }

            $rows .= self::labelledRow($escape((string) $label), $cell);
        }

        return $rows;
    }

    /**
     * One label/value row. The value cell states `color` itself rather than inheriting it from the
     * enclosing table — Outlook does not carry `color` across that boundary, which is what left the
     * notification's field values black on the dark card.
     *
     * Both arguments are already escaped or trusted markup; this only assembles the cells.
     */
    private static function labelledRow(string $label, string $value): string
    {
        return '<tr><td style="padding:4px 0;width:120px;color:' . self::INK_MUTED . ';">' . $label
            . '</td><td style="padding:4px 0;color:' . self::INK . ';">' . $value . '</td></tr>';
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

        // One flag decides both sign-offs: `minimalFooter` marks a template as internal, and internal
        // mail is signed by the platform, not by the team. They used to disagree — every plain-text
        // part closed with "The Perego Team" even on an automated alert.
        $internal = (bool) ($parts['minimalFooter'] ?? false);

        return [
            'subject' => $parts['subject'],
            'html' => $this->shell($locale, $parts['eyebrow'], $parts['subject'], $parts['inner'], $internal),
            'text' => $this->textDoc($locale, $parts['text'], $internal),
        ];
    }

    // ---- Templates ---------------------------------------------------------

    /** @param array<string,string> $ctx @return array<string,string> */
    private function contactConfirmation(string $locale, array $ctx): array
    {
        $ar = $locale === 'ar';
        $name = $this->esc($ctx['name'] ?? '');
        $message = $this->esc($ctx['message'] ?? '');

        $subject = $ar ? 'شكرًا لتواصلك — بيريجو' : 'Thanks for reaching out — Perego';
        $heading = $ar ? "شكرًا لك، {$name}!" : "Thank you, {$name}!";
        $intro = $ar
            ? 'لقد استلمنا رسالتك وسيعاود أحد أعضاء فريقنا التواصل معك قريبًا. وفي هذه الأثناء، إليك نسخة مما أرسلته إلينا.'
            : "We've received your message and a member of our team will get back to you shortly. In the meantime, here's a copy of what you sent us.";
        $msgLabel = $ar ? 'رسالتك' : 'Your message';
        $cta = $ar ? 'استعرض أعمالنا' : 'Visit our work';

        // No "Your email: …" line. This email is already IN the reader's inbox, so telling them their
        // own address says nothing — it just read like leaked debug output (client report 2026-07-27).
        $summary = $this->summaryBox(
            $this->eyebrowLine($msgLabel)
            . '<p style="margin:0;font-size:15px;line-height:1.6;color:' . self::INK . ';">' . $message . '</p>'
        );

        $inner = $this->checkHeading($heading, $intro) . $summary . $this->ctaRow($this->siteUrl . '/work', $cta);

        $text = $this->stripTitle($heading) . "\n\n" . $intro . "\n\n"
            . $msgLabel . ": " . ($ctx['message'] ?? '')
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
            ? ['brief' => 'ملخص المشروع', 'services' => 'الخدمة/الخدمات', 'budget' => 'الميزانية', 'subject' => 'الموضوع', 'message' => 'الرسالة', 'email' => 'البريد الإلكتروني', 'phone' => 'الهاتف', 'company' => 'الشركة']
            : ['brief' => 'Project brief', 'services' => 'Service(s)', 'budget' => 'Budget', 'subject' => 'Subject', 'message' => 'Message', 'email' => 'Email', 'phone' => 'Phone', 'company' => 'Company'];
        $cta = $ar ? 'شاهد أحدث أعمالنا' : 'See our recent work';

        // Contact details are rows in the summary table like every other field, not a trailing
        // "Your details: a • b • c" run. That line read as a malformed mail header and repeated the
        // reader's own address at them; a labelled row says what each value is (client report
        // 2026-07-27). Each row is omitted when its value is empty.
        $details = [
            $lbl['services'] => (string) ($ctx['services'] ?? ''),
            $lbl['budget'] => (string) ($ctx['budget'] ?? ''),
            $lbl['subject'] => (string) ($ctx['subject'] ?? ''),
            $lbl['email'] => (string) ($ctx['email'] ?? ''),
            $lbl['phone'] => (string) ($ctx['phone'] ?? ''),
            $lbl['company'] => (string) ($ctx['company'] ?? ''),
        ];
        $details = array_filter($details, static fn (string $v): bool => trim($v) !== '');

        $rows = '';
        foreach ($details as $label => $value) {
            $rows .= $this->kvRow((string) $label, $this->esc($value));
        }

        $summary = $this->summaryBox(
            $this->eyebrowLine($lbl['brief'])
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;color:' . self::INK . ';line-height:1.5;">' . $rows . '</table>'
            . $this->eyebrowLine($lbl['message'], '14px 0 6px')
            . '<p style="margin:0;font-size:15px;line-height:1.6;color:' . self::INK . ';">' . $this->esc($ctx['message'] ?? '') . '</p>'
        );

        $inner = $this->checkHeading($heading, $intro) . $summary . $this->ctaRow($this->siteUrl . '/work', $cta);

        $text = $this->stripTitle($heading) . "\n\n" . $intro . "\n\n";
        foreach ($details as $label => $value) {
            $text .= $label . ': ' . $value . "\n";
        }
        $text .= $lbl['message'] . ': ' . ($ctx['message'] ?? '')
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
            ? '<p style="margin:0 0 8px;font-size:15px;color:' . self::INK . ';">' . $lbl['pf'] . ': <a href="' . $portfolio . '" style="color:' . self::LINK . ';">' . $portfolio . '</a></p>'
            : '';

        $summary = $this->summaryBox(
            $this->eyebrowLine($lbl['sum'])
            . $pfLine
            . '<p style="margin:0;font-size:15px;color:' . self::INK . ';">' . $lbl['cv'] . ': ' . $cv . '</p>'
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

        $inner = '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:34px 36px 6px;">'
            . '<h1 style="margin:0 0 6px;font-size:22px;font-weight:700;color:' . self::INK . ';">' . $heading . '</h1>'
            . '<p style="margin:0 0 20px;font-size:13px;color:' . self::INK_SUBTLE . ';">' . $received . '</p></td></tr>'
            . '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:0 36px 14px;">'
            . self::panel(
                '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;color:' . self::INK . ';line-height:1.5;">'
                . $fieldsHtml . '</table>',
                '18px 22px',
            )
            . '</td></tr>'
            . $this->mailtoCtaRow($reply, $cta);

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

        $inner = '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:34px 36px 6px;">'
            . '<h1 style="margin:0 0 6px;font-size:22px;font-weight:700;color:' . self::INK . ';">' . $heading . '</h1>'
            . '<p style="margin:0 0 20px;font-size:14px;line-height:1.5;color:' . self::INK_SUBTLE . ';">' . $onLabel . ' <a href="' . $postUrl . '" style="color:' . self::LINK . ';">' . $postTitle . '</a> &nbsp;•&nbsp; ' . $submittedAt . '</p></td></tr>'
            . '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:0 36px 14px;">'
            . self::panel(
                '<p style="margin:0 0 4px;font-size:15px;font-weight:700;color:' . self::INK . ';">' . $cName . '</p>'
                . '<p style="margin:0 0 14px;font-size:13px;color:' . self::INK_MUTED . ';">' . $cEmail . '</p>'
                . '<p style="margin:0;font-size:15px;line-height:1.6;color:' . self::INK . ';border-inline-start:3px solid ' . self::ACCENT . ';padding-inline-start:14px;">' . $body . '</p>',
                '20px 22px',
            )
            . '</td></tr>'
            . '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:16px 36px 36px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
            . '<td bgcolor="' . self::ACCENT . '" style="background:' . self::ACCENT . ';border-radius:9px;">'
            . '<a href="' . $approve . '" style="display:inline-block;background:' . self::ACCENT . ';color:' . self::ACCENT_INK . ';text-decoration:none;font-weight:700;font-size:15px;padding:13px 30px;border-radius:9px;">' . $approveCta . '</a></td>'
            . '<td style="width:10px;">&nbsp;</td>'
            . '<td style="border-radius:9px;">'
            . '<a href="mailto:' . $this->escUrl($ctx['commenter_email'] ?? '', true) . '" style="display:inline-block;border:1px solid ' . self::LINK . ';color:' . self::LINK . ';text-decoration:none;font-weight:700;font-size:15px;padding:12px 26px;border-radius:9px;">' . $replyCta . '</a></td>'
            . '</tr></table></td></tr>';

        $text = $this->stripTitle($heading) . "\n" . $onLabel . ' ' . $this->stripTitle($postTitle) . ' • ' . $this->stripTitle($submittedAt) . "\n\n"
            . $this->stripTitle($cName) . ' <' . ($ctx['commenter_email'] ?? '') . ">\n" . ($ctx['comment_body'] ?? '')
            . "\n\n" . $this->stripTitle($approveCta) . ': ' . ($ctx['approve_url'] ?? '');

        return ['subject' => $subject, 'eyebrow' => $ar ? 'تعليق جديد' : 'New comment', 'inner' => $inner, 'text' => $text, 'minimalFooter' => true];
    }

    /**
     * A person's reply, typed in the Submissions inbox, in the Perego shell.
     *
     * This is the only template whose body is authored rather than composed: the operator's HTML is
     * dropped into the card as-is, so it must arrive already sanitized by the admin surface that
     * accepted it — escaping it here would print their formatting as source. Everything around it is
     * the same header, card and team sign-off as every other Perego email, which is exactly the point:
     * a reply used to leave as bare text on the client's default white background while every
     * automated message from the same site was branded (client report 2026-07-27).
     *
     * @param array<string,string> $ctx
     * @return array<string,mixed>
     */
    private function reply(string $locale, array $ctx): array
    {
        $ar = $locale === 'ar';
        $body = (string) ($ctx['body'] ?? ''); // pre-sanitized operator HTML

        // A subject line is plain text, so any markup in it is either a mistake or an attack. Strip
        // rather than escape: `render()` hands this same string to the mail `Subject:` header AND to
        // the shell's `<title>`, and an HTML-escaped header would ship a literal `&lt;` to the reader.
        $subject = trim(strip_tags((string) ($ctx['subject'] ?? '')));

        if ($subject === '') {
            $subject = $ar ? 'رسالة من بيريجو' : 'A message from Perego';
        }

        $inner = '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:40px 36px 8px;">'
            . '<h1 style="margin:0 0 18px;font-size:24px;font-weight:700;color:' . self::INK . ';">' . $this->esc($subject) . '</h1></td></tr>'
            . '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:0 36px 30px;">'
            . self::panel($body, '22px 24px', 'font-size:15px;line-height:1.7;')
            . '</td></tr>';

        return [
            'subject' => $subject,
            'eyebrow' => $ar ? 'رد من الفريق' : 'A reply from the team',
            'inner' => $inner,
            'text' => $this->stripTitle($subject) . "\n\n" . $this->stripTitle($body),
        ];
    }

    // ---- Shared shell ------------------------------------------------------

    private function shell(string $locale, string $eyebrow, string $subject, string $innerRows, bool $minimalFooter): string
    {
        $dir = $locale === 'ar' ? 'rtl' : 'ltr';
        $lang = $locale === 'ar' ? 'ar' : 'en';

        // `supported-color-schemes: dark` alongside `color-scheme`: the shell is already dark, so a
        // client that auto-inverts only succeeds in flipping the text out from under it.
        return '<!DOCTYPE html><html lang="' . $lang . '" dir="' . $dir . '"><head><meta charset="UTF-8" />'
            . '<meta name="viewport" content="width=device-width, initial-scale=1.0" />'
            . '<meta name="color-scheme" content="dark" /><meta name="supported-color-schemes" content="dark" />'
            . '<title>' . $subject . '</title></head>'
            . '<body bgcolor="' . self::PAGE_BG . '" style="margin:0;padding:0;background:' . self::PAGE_BG . ';color:' . self::INK . ';font-family:\'Open Sans\',Arial,Helvetica,sans-serif;">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:' . self::PAGE_BG . ';">' . $subject . '</div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="' . self::PAGE_BG . '" style="background:' . self::PAGE_BG . ';padding:32px 12px;"><tr><td align="center" bgcolor="' . self::PAGE_BG . '">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" bgcolor="' . self::CARD_BG . '" style="width:600px;max-width:100%;background:' . self::CARD_BG . ';border-radius:20px;overflow:hidden;border:1px solid ' . self::CARD_BORDER . ';">'
            . $this->header($eyebrow)
            . $innerRows
            . $this->footer($minimalFooter)
            . '</table></td></tr></table></body></html>';
    }

    private function header(string $eyebrow): string
    {
        $brand = $this->logoUrl !== ''
            ? '<img src="' . $this->escUrl($this->logoUrl) . '" alt="Perego" width="140" height="43" style="display:block;border:0;height:auto;" />'
            : '<strong style="color:' . self::INK . ';font-size:22px;">Perego</strong>';

        // Solid `background-color` first, then the gradient as `background-image`. A client that
        // ignores gradients keeps the violet; declaring the gradient through the `background`
        // shorthand instead left this row with no background at all — white brand on white.
        return '<tr><td bgcolor="' . self::HEADER_BG . '" style="background-color:' . self::HEADER_BG . ';background-image:linear-gradient(120deg,#4a0d8f,' . self::HEADER_BG . ' 60%,#7d24c4);padding:30px 36px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="color:' . self::INK . ';">' . $brand . '</td>'
            . '<td align="right" style="font-size:12px;color:' . self::INK_HEADER . ';text-transform:uppercase;letter-spacing:0.14em;">' . $this->esc($eyebrow) . '</td>'
            . '</tr></table></td></tr>';
    }

    /**
     * The internal-notification sign-off. It names the sender as a service rather than a person, so a
     * team member reading it knows at a glance that nobody is waiting on a reply to *this* message —
     * the client is reachable through the Reply-To, not through the platform.
     */
    private const SYSTEM_SIGNATURE = ['Best regards,', 'System Notification Service', 'Perego Web Platform'];

    private function footer(bool $minimal): string
    {
        $cell = '<tr><td bgcolor="' . self::FOOTER_BG . '" style="background:' . self::FOOTER_BG
            . ';padding:%s 36px;border-top:1px solid ' . self::FOOTER_RULE . ';">';

        if ($minimal) {
            [$regards, $service, $platform] = self::SYSTEM_SIGNATURE;

            return sprintf($cell, '22px')
                . '<p style="margin:0 0 6px;font-size:13px;line-height:1.6;color:' . self::INK_FOOTER . ';">' . $regards . '<br />'
                . '<strong style="color:' . self::INK . ';">' . $service . '</strong><br />' . $platform . '</p>'
                . '<p style="margin:0;font-size:12px;color:' . self::INK_FOOTER_FAINT . ';">Automated notification from the Perego website. Do not share externally.</p></td></tr>';
        }

        return sprintf($cell, '26px')
            . '<p style="margin:0 0 6px;font-size:13px;color:' . self::INK_FOOTER . ';">Best regards,<br /><strong style="color:' . self::INK . ';">The Perego Team</strong> &nbsp;•&nbsp; <a href="mailto:' . self::CONTACT_EMAIL . '" style="color:' . self::LINK . ';text-decoration:none;">' . self::CONTACT_EMAIL . '</a></p>'
            . '<p style="margin:0;font-size:12px;color:' . self::INK_FOOTER_FAINT . ';">© Perego Creative Studio. Serving creators across the Arab region.</p></td></tr>';
    }

    // ---- Content helpers ---------------------------------------------------

    private function checkHeading(string $heading, string $intro): string
    {
        return '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:40px 36px 8px;">'
            . '<div style="display:inline-block;width:54px;height:54px;border-radius:50%;background:' . self::CHECK_BG . ';text-align:center;line-height:54px;font-size:26px;color:' . self::ACCENT . ';">&#10003;</div>'
            . '<h1 style="margin:22px 0 10px;font-size:26px;font-weight:700;color:' . self::INK . ';">' . $heading . '</h1>'
            . '<p style="margin:0 0 18px;font-size:16px;line-height:1.6;color:' . self::INK_SOFT . ';">' . $intro . '</p></td></tr>';
    }

    private function summaryBox(string $inner): string
    {
        return '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:8px 36px 12px;">' . self::panel($inner, '20px 22px') . '</td></tr>';
    }

    /**
     * The violet inset panel every template puts its detail in. One definition, so the summary box,
     * the notification field table, the comment quote and the reply body cannot drift apart — they
     * had four copies of the same two `rgba()` values between them.
     */
    private static function panel(string $inner, string $padding, string $cellStyle = ''): string
    {
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="' . self::PANEL_BG
            . '" style="background:' . self::PANEL_BG . ';border:1px solid ' . self::PANEL_BORDER . ';border-radius:14px;">'
            . '<tr><td bgcolor="' . self::PANEL_BG . '" style="padding:' . $padding . ';color:' . self::INK . ';' . $cellStyle . '">'
            . $inner . '</td></tr></table>';
    }

    private function eyebrowLine(string $label, string $margin = '0 0 6px'): string
    {
        return '<p style="margin:' . $margin . ';font-size:12px;text-transform:uppercase;letter-spacing:0.14em;color:' . self::ACCENT . ';font-weight:700;">' . $label . '</p>';
    }

    private function kvRow(string $label, string $value): string
    {
        return self::labelledRow($label, $value);
    }

    /** The same pink button, pointed at a mailto: address rather than a page. */
    private function mailtoCtaRow(string $email, string $label): string
    {
        $address = $this->escUrl($email, true);

        return '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:16px 36px 36px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
            . '<td bgcolor="' . self::ACCENT . '" style="background:' . self::ACCENT . ';border-radius:9px;">'
            . '<a href="mailto:' . $address . '" style="display:inline-block;background:' . self::ACCENT . ';color:' . self::ACCENT_INK . ';text-decoration:none;font-weight:700;font-size:15px;padding:13px 30px;border-radius:9px;">' . $label . '</a>'
            . '</td></tr></table></td></tr>';
    }

    /**
     * The pink call-to-action. The label is near-black violet, which is only legible on the pink
     * field — so the field is declared on a `bgcolor` cell as well as on the anchor, rather than
     * trusting a client to honour `background` on an inline-block `<a>`.
     */
    private function ctaRow(string $url, string $label): string
    {
        return '<tr><td bgcolor="' . self::CARD_BG . '" style="padding:22px 36px 36px;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
            . '<td bgcolor="' . self::ACCENT . '" style="background:' . self::ACCENT . ';border-radius:9px;">'
            . '<a href="' . $this->escUrl($url) . '" style="display:inline-block;background:' . self::ACCENT . ';color:' . self::ACCENT_INK . ';text-decoration:none;font-weight:700;font-size:15px;padding:14px 32px;border-radius:9px;">' . $label . '</a>'
            . '</td></tr></table></td></tr>';
    }

    private function textDoc(string $locale, string $body, bool $internal): string
    {
        if ($internal) {
            return $body . "\n\n" . implode("\n", self::SYSTEM_SIGNATURE);
        }

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
