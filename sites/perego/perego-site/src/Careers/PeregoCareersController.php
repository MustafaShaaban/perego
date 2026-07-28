<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Careers;

defined('ABSPATH') || exit;

use PeregoSite\Email\MailBaseUrl;
use PeregoSite\Email\PeregoEmailRenderer;
use PeregoSite\Email\PeregoMailer;
use PeregoSite\Email\TeamRecipient;
use WP_REST_Request;
use WP_REST_Response;

/**
 * The "Join us" / CV submission endpoint (spec Phase 7) at POST `perego/v1/careers/apply`. A public,
 * anonymous form, so it is gated by a honeypot + a per-IP rate limit rather than a nonce. It applies
 * the approved handoff CV policy (pdf/doc/docx ≤ 10 MB) via WordPress's own filetype check plus a
 * finfo content-type sniff (never trusting the browser-supplied MIME), stores the CV as a private
 * attachment, records the application against the standing "Open Application" job, and sends the
 * applicant Perego's branded join-confirmation plus the team an admin-notification. Every handoff
 * state maps to a distinct HTTP status + code the form's view.js renders (invalid, spam, rate_limit,
 * wrong_type, too_large, server_error, success).
 */
final class PeregoCareersController
{
    /** The slug applications are filed under in the CoreX Submissions inbox. */
    public const SUBMISSION_SLUG = 'perego-careers';

    /** The label that slug shows as, in the inbox filter and the notification subject. */
    public const FORM_NAME = 'Careers application';

    /** mime => allowed extensions (matches the CoreX Careers CV policy). */
    private const CV_TYPES = [
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
    ];

    private const MAX_BYTES = 10 * 1024 * 1024;

    private const RATE_LIMIT = 5;         // submissions
    private const RATE_WINDOW = 600;      // per 10 minutes, per IP

    public function __construct(
        private readonly PeregoMailer $mailer,
        private readonly TeamRecipient $recipient,
    ) {
    }

    public function register(): void
    {
        register_rest_route('perego/v1', '/careers/apply', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'apply'],
        ]);
    }

    public function apply(WP_REST_Request $request): WP_REST_Response
    {
        // 1) Honeypot — a filled hidden field means a bot.
        if (trim((string) $request->get_param('perego_hp')) !== '') {
            return $this->fail('spam', 422);
        }

        // 2) Rate limit per IP.
        if ($this->rateLimited()) {
            return $this->fail('rate_limit', 429);
        }

        // 3) Required, sanitized fields.
        $name = sanitize_text_field((string) $request->get_param('name'));
        $email = sanitize_email((string) $request->get_param('email'));
        $portfolio = esc_url_raw(trim((string) $request->get_param('portfolio')));

        if ($name === '' || ! is_email($email)) {
            return $this->fail('invalid', 422);
        }

        // 4) CV file — presence, then the CoreX size/MIME/extension policy, then a finfo sniff.
        $file = $_FILES['cv'] ?? null;
        if (! is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $this->fail('cv_required', 422);
        }

        $reason = $this->validateCv($file);
        if ($reason !== '') {
            return $this->fail($reason, 422);
        }

        // 5) Store the CV as a private attachment.
        $attachmentId = $this->storeCv($file, $name);
        if ($attachmentId === 0) {
            return $this->fail('server_error', 500);
        }

        $filename = sanitize_file_name((string) ($file['name'] ?? 'cv'));

        // 6) Record the application (best-effort) + notify.
        $this->record($name, $email, $portfolio, $attachmentId);
        $this->recordSubmission($name, $email, $portfolio, $filename, $attachmentId);
        $this->touchRateLimit();
        $this->notify($name, $email, $portfolio, $filename, $attachmentId);

        return new WP_REST_Response(['ok' => true], 200);
    }

    /** @param array<string,mixed> $file @return string '' when valid, else a state code */
    private function validateCv(array $file): string
    {
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return 'cv_required';
        }
        if ($size > self::MAX_BYTES) {
            return 'too_large';
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $tmp = (string) ($file['tmp_name'] ?? '');

        // Sniff the real MIME from file content — never trust the browser-supplied type.
        $realMime = '';
        if ($tmp !== '' && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $realMime = (string) finfo_file($finfo, $tmp);
                finfo_close($finfo);
            }
        }

        // WordPress's own extension→mime check as the authority, cross-checked with the sniff.
        $checked = wp_check_filetype_and_ext($tmp, (string) ($file['name'] ?? ''));
        $wpMime = (string) ($checked['type'] ?? '');

        $allowed = array_keys(self::CV_TYPES);
        $mimeOk = in_array($wpMime, $allowed, true)
            && ($realMime === '' || in_array($realMime, [...$allowed, 'application/octet-stream', 'application/zip'], true));
        $extOk = false;
        foreach (self::CV_TYPES as $exts) {
            if (in_array($extension, $exts, true)) {
                $extOk = true;
            }
        }

        if (! $extOk || ! $mimeOk) {
            return 'wrong_type';
        }

        return '';
    }

    /** @param array<string,mixed> $file */
    private function storeCv(array $file, string $name): int
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // wp_handle_upload expects `mimes` as extension => mime (not the mime => [ext] policy shape).
        $overrides = [
            'test_form' => false,
            'mimes' => [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];
        $moved = wp_handle_upload($file, $overrides);
        if (! is_array($moved) || isset($moved['error'])) {
            return 0;
        }

        $attachment = [
            'post_mime_type' => (string) $moved['type'],
            'post_title' => sanitize_text_field($name) . ' — CV',
            'post_status' => 'private',
            'post_content' => '',
        ];
        $id = wp_insert_attachment($attachment, (string) $moved['file']);

        return is_wp_error($id) ? 0 : (int) $id;
    }

    private function record(string $name, string $email, string $portfolio, int $attachmentId): void
    {
        if (! class_exists(\Corex\Boot::class)) {
            return;
        }

        try {
            $container = \Corex\Boot::app()->container();
            if (! $container->has(\Corex\Careers\Application\ApplicationStore::class)) {
                return;
            }
            $container->make(\Corex\Careers\Application\ApplicationStore::class)->create([
                'job_id' => $this->openApplicationJobId(),
                'name' => $name,
                'email' => $email,
                'cover_letter' => $portfolio !== '' ? 'Portfolio: ' . $portfolio : '',
                'cv_attachment' => $attachmentId,
                'status' => 'new',
            ]);
        } catch (\Throwable) {
            // Storage is best-effort; the application email is the primary record.
        }
    }

    /**
     * Mirror the application into the CoreX Submissions inbox.
     *
     * Careers is a bespoke REST endpoint rather than a `FormRegistry` form, so nothing on the submit
     * path ever wrote a `corex_submission` — applications existed only in `corex_applications`, and
     * the client looked for them next to Contact and Start-a-Project and found nothing (report
     * 2026-07-27). Writing one here puts them where every other form's submissions already are, while
     * {@see \Corex\Careers\Application\ApplicationStore} keeps the recruiting record (status, CV id).
     *
     * The record carries the CV's **URL**, not just its filename. A filename is not a document: the
     * attachment id lived only in `corex_applications`, so an operator looking at the submission had
     * no way to reach the actual file from the screen they were on (client report 2026-07-27, "I
     * still can't view the attachment from the submission page"). The inbox renders an http(s) value
     * as a link, so storing the URL is all that is needed on this side.
     *
     * Best-effort, like {@see self::record()}: the applicant's mail is the primary record, and a
     * storage failure must not fail an accepted application.
     */
    private function recordSubmission(string $name, string $email, string $portfolio, string $filename, int $attachmentId): void
    {
        if (! class_exists(\Corex\Boot::class)) {
            return;
        }

        try {
            $container = \Corex\Boot::app()->container();
            if (! $container->has(\Corex\Forms\Submission\SubmissionStore::class)) {
                return;
            }

            $values = [
                'name' => $name,
                'email' => $email,
                'portfolio' => $portfolio,
                'cv' => $filename,
            ];

            $cvUrl = $this->cvUrl($attachmentId);
            if ($cvUrl !== '') {
                $values['cv_url'] = $cvUrl;
            }

            $container->make(\Corex\Forms\Submission\SubmissionStore::class)->save(self::SUBMISSION_SLUG, $values);
        } catch (\Throwable) {
            // See above: never fail an accepted application on a storage error.
        }
    }

    private function notify(string $name, string $email, string $portfolio, string $filename, int $attachmentId): void
    {
        $locale = str_starts_with((string) get_locale(), 'ar') ? 'ar' : 'en';

        // No Reply-To: this goes to the applicant, who should not be replying to themselves.
        $this->mailer->send('join-confirmation', $locale, $email, [
            'name' => $name,
            'portfolio' => $portfolio,
            'cv_filename' => $filename,
        ]);

        // The configured forms recipient, not `admin_email`. This endpoint used to read the WordPress
        // option directly, which on this install is still the default `admin@example.com` — so every
        // application (and its CV link) was delivered to a mailbox nobody owns, while the contact and
        // brief forms arrived normally. Client report 2026-07-27.
        $hr = $this->recipient->address();
        if ($hr === '') {
            return;
        }

        // The CV cannot ride along as an attachment — the mail stack has no attachments field at all
        // (MailRequest/EmailMessage carry none and WpMailDriver calls wp_mail with four arguments), so
        // HR got a bare filename and no way to reach the file. A link is also the better answer for a
        // CV: personal data stays out of mail servers and inbox backups.
        $cv = $attachmentId > 0 && $this->cvUrl($attachmentId) !== ''
            ? ['text' => $filename, 'url' => $this->cvUrl($attachmentId)]
            : $filename;

        $fields = [
            'Name' => $name,
            'Email' => $email,
            'Portfolio' => $portfolio !== '' ? $portfolio : '—',
            'CV' => $cv,
        ];

        $manage = $this->adminUrl($attachmentId);
        if ($manage !== '') {
            $fields['In admin'] = ['text' => 'Open in WordPress', 'url' => $manage];
        }

        // The applicant's address as a real Reply-To, so HR can just hit Reply. It was previously only
        // rendered as the template's mailto: button.
        $this->mailer->send('admin-notification', $locale, $hr, [
            'form_name' => self::FORM_NAME,
            'submitted_at' => (string) current_time('mysql'),
            'reply_email' => $email,
            'fields_html' => PeregoEmailRenderer::fieldRows($fields),
        ], $email);
    }

    /** The stored CV's URL, rebased onto the public mail host so the link resolves from an inbox. */
    private function cvUrl(int $attachmentId): string
    {
        $url = (string) wp_get_attachment_url($attachmentId);

        return $url === '' ? '' : MailBaseUrl::rebase($url);
    }

    /**
     * The wp-admin edit screen for the stored CV.
     *
     * Built from `admin_url()` rather than `get_edit_post_link()`, which returns null unless the
     * CURRENT user can edit the post. This runs on an anonymous public submission, so there is no
     * current user and that helper can only ever return null here — the row would never have appeared.
     * Emitting the URL is safe: wp-admin still authenticates whoever follows it.
     */
    private function adminUrl(int $attachmentId): string
    {
        if ($attachmentId <= 0) {
            return '';
        }

        return MailBaseUrl::rebase(admin_url('post.php?post=' . $attachmentId . '&action=edit'));
    }

    private function openApplicationJobId(): int
    {
        $ids = get_posts([
            'post_type' => 'corex_job',
            'name' => 'open-application',
            'post_status' => 'publish',
            'numberposts' => 1,
            'fields' => 'ids',
        ]);

        return isset($ids[0]) ? (int) $ids[0] : 0;
    }

    private function clientIpHash(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return md5('perego-careers|' . $ip);
    }

    private function rateLimited(): bool
    {
        return (int) get_transient('perego_careers_' . $this->clientIpHash()) >= self::RATE_LIMIT;
    }

    private function touchRateLimit(): void
    {
        $key = 'perego_careers_' . $this->clientIpHash();
        set_transient($key, (int) get_transient($key) + 1, self::RATE_WINDOW);
    }

    private function fail(string $code, int $status): WP_REST_Response
    {
        return new WP_REST_Response(['ok' => false, 'error' => $code], $status);
    }
}
