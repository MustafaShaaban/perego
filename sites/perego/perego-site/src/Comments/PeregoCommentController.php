<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Comments;

defined('ABSPATH') || exit;

use PeregoSite\Blocks\JournalCommentsRenderer;
use PeregoSite\Content\GlobalContent;
use WP_Comment;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Secure AJAX comment submission at POST `perego/v1/comments`, so the journal single can post
 * comments/replies without a full reload and show a thank-you / awaiting-review message. Layered
 * defence (the owner's anti-spam ask), mirroring the proven PeregoCareersController recipe:
 *   1. a `wp_rest` nonce (CSRF),
 *   2. a honeypot (`perego_hp`) — a filled hidden field means a bot,
 *   3. a per-IP + per-post transient rate limit (flood/dummy-comment throttle),
 *   4. WordPress's own `wp_handle_comment_submission()` — comment-flood, duplicate, disallowed-keys/
 *      blocklist, Akismet (if active) and moderation (first-time authors are held here, since
 *      `comment_previously_approved` is on), so spam never appears until approved.
 *
 * Returns `{ok:true, status:'approved'|'moderation', commentHtml?, count}`; an approved comment is
 * rendered with JournalCommentsRenderer::renderCard() for the client to insert on the fly (held
 * comments return status only — they must not appear until moderated).
 */
final class PeregoCommentController
{
    private const RATE_LIMIT = 5;     // comments
    private const RATE_WINDOW = 300;  // per 5 minutes, per IP+post

    public function register(): void
    {
        register_rest_route('perego/v1', '/comments', [
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => [$this, 'submit'],
        ]);
    }

    public function submit(WP_REST_Request $request): WP_REST_Response
    {
        // 1) CSRF nonce (the block prints a fresh wp_rest nonce; the page is not full-page cached).
        $nonce = (string) ($request->get_header('X-WP-Nonce') ?: $request->get_param('_wpnonce'));
        if (! wp_verify_nonce($nonce, 'wp_rest')) {
            return $this->fail('session', 403);
        }

        // 2) Honeypot.
        if (trim((string) $request->get_param('perego_hp')) !== '') {
            return $this->fail('spam', 422);
        }

        $postId = absint((string) $request->get_param('comment_post_ID'));
        if ($postId === 0 || ! comments_open($postId)) {
            return $this->fail('closed', 422);
        }

        // 3) Rate limit per IP + post.
        if ($this->rateLimited($postId)) {
            return $this->fail('rate_limit', 429);
        }

        // 4) Sanitize. comment_parent must belong to the same post (no cross-post threading).
        $parent = absint((string) $request->get_param('comment_parent'));
        if ($parent > 0) {
            $parentComment = get_comment($parent);
            if (! $parentComment instanceof WP_Comment || (int) $parentComment->comment_post_ID !== $postId) {
                $parent = 0;
            }
        }

        $commentData = [
            'comment_post_ID' => $postId,
            'author' => sanitize_text_field((string) $request->get_param('author')),
            'email' => sanitize_email((string) $request->get_param('email')),
            'url' => '',
            'comment' => trim((string) $request->get_param('comment')),
            'comment_parent' => $parent,
        ];

        if ($commentData['author'] === '' || ! is_email($commentData['email']) || $commentData['comment'] === '') {
            return $this->fail('invalid', 422);
        }

        // 5) Hand to WordPress: flood/duplicate/blocklist/moderation all happen here.
        $comment = wp_handle_comment_submission($commentData);
        if ($comment instanceof WP_Error) {
            return $this->fail($this->mapError($comment), $this->statusForError($comment));
        }

        $this->touchRateLimit($postId);

        $approved = wp_get_comment_status($comment) === 'approved';
        $payload = [
            'ok' => true,
            'status' => $approved ? 'approved' : 'moderation',
            'count' => (int) get_comments(['post_id' => $postId, 'status' => 'approve', 'count' => true]),
        ];

        if ($approved) {
            $payload['commentHtml'] = $this->renderCard($comment);
        }

        return new WP_REST_Response($payload, 201);
    }

    private function renderCard(WP_Comment $comment): string
    {
        $locale = str_starts_with((string) get_locale(), 'ar') ? 'ar' : 'en';
        $journal = (new GlobalContent($locale))->journal();

        return (new JournalCommentsRenderer())->renderCard([
            'id' => (int) $comment->comment_ID,
            'author' => (string) $comment->comment_author,
            'date' => (string) get_comment_date('', $comment),
            'text' => (string) $comment->comment_content,
            'isReply' => (int) $comment->comment_parent !== 0,
        ], ['reply' => $journal['reply']]);
    }

    /** Map WordPress's comment WP_Error codes to the view.js message keys. */
    private function mapError(WP_Error $error): string
    {
        return match ($error->get_error_code()) {
            'comment_flood' => 'flood',
            'duplicate_comment' => 'duplicate',
            'comment_on_draft', 'comment_on_password_protected', 'comment_on_trash', 'comments_closed' => 'closed',
            'require_valid_comment', 'not_logged_in', 'require_name_email' => 'invalid',
            default => 'server_error',
        };
    }

    private function statusForError(WP_Error $error): int
    {
        return match ($error->get_error_code()) {
            'comment_flood' => 429,
            'duplicate_comment' => 409,
            default => 422,
        };
    }

    private function clientIpHash(int $postId): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return md5('perego-comments|' . $postId . '|' . $ip);
    }

    private function rateLimited(int $postId): bool
    {
        return (int) get_transient('perego_comments_' . $this->clientIpHash($postId)) >= self::RATE_LIMIT;
    }

    private function touchRateLimit(int $postId): void
    {
        $key = 'perego_comments_' . $this->clientIpHash($postId);
        set_transient($key, (int) get_transient($key) + 1, self::RATE_WINDOW);
    }

    private function fail(string $code, int $status): WP_REST_Response
    {
        return new WP_REST_Response(['ok' => false, 'error' => $code], $status);
    }
}
