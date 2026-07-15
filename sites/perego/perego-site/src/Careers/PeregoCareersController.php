<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Careers;

defined('ABSPATH') || exit;

use PeregoSite\Email\PeregoMailer;
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
    /** mime => allowed extensions (matches the CoreX Careers CV policy). */
    private const CV_TYPES = [
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
    ];

    private const MAX_BYTES = 10 * 1024 * 1024;

    private const RATE_LIMIT = 5;         // submissions
    private const RATE_WINDOW = 600;      // per 10 minutes, per IP

    public function __construct(private readonly PeregoMailer $mailer)
    {
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
        $this->touchRateLimit();
        $this->notify($name, $email, $portfolio, $filename);

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

    private function notify(string $name, string $email, string $portfolio, string $filename): void
    {
        $locale = str_starts_with((string) get_locale(), 'ar') ? 'ar' : 'en';

        $this->mailer->send('join-confirmation', $locale, $email, [
            'name' => $name,
            'portfolio' => $portfolio,
            'cv_filename' => $filename,
        ], $email);

        $hr = (string) (get_option('admin_email'));
        if ($hr !== '') {
            $this->mailer->send('admin-notification', $locale, $hr, [
                'form_name' => 'Careers application',
                'submitted_at' => (string) current_time('mysql'),
                'reply_email' => $email,
                'fields_html' => $this->fieldsHtml([
                    'Name' => $name,
                    'Email' => $email,
                    'Portfolio' => $portfolio !== '' ? $portfolio : '—',
                    'CV' => $filename,
                ]),
            ]);
        }
    }

    /** @param array<string,string> $fields Pre-rendered, escaped <tr> rows for the admin email. */
    private function fieldsHtml(array $fields): string
    {
        $rows = '';
        foreach ($fields as $label => $value) {
            $rows .= '<tr><td style="padding:4px 0;color:rgba(255,255,255,0.6);width:120px;">' . esc_html($label)
                . '</td><td style="padding:4px 0;">' . esc_html($value) . '</td></tr>';
        }

        return $rows;
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
