<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

/**
 * Replaces WordPress's plain-text comment-moderation email with Perego's branded comment-notification
 * (spec Phase 7/8): on a new comment it emails the moderator the exact handoff template — commenter,
 * body, the post it lands on, and an approve link — then suppresses the native notifications so the
 * moderator gets one email, not two. Sends through PeregoMailer (the CoreX Mailer seam). Non-fatal:
 * a comment with no post, or an absent mailer, is simply skipped.
 */
final class PeregoCommentNotifier
{
    public function __construct(private readonly PeregoMailer $mailer)
    {
    }

    public function register(): void
    {
        // Send the branded notification, then silence WordPress's own (so there is exactly one).
        add_action('comment_post', [$this, 'onCommentPosted'], 10, 2);
        add_filter('notify_moderator', '__return_false');
        add_filter('notify_post_author', '__return_false');
    }

    /**
     * @param int|string $commentId
     * @param int|string $approved  1 approved, 0 pending, 'spam'
     */
    public function onCommentPosted($commentId, $approved): void
    {
        if ($approved === 'spam') {
            return;
        }

        $comment = get_comment((int) $commentId);
        if (! $comment instanceof \WP_Comment) {
            return;
        }

        $post = get_post((int) $comment->comment_post_ID);
        if (! $post instanceof \WP_Post) {
            return;
        }

        $recipient = (string) get_option('admin_email');
        if ($recipient === '') {
            return;
        }

        $locale = str_starts_with((string) get_locale(), 'ar') ? 'ar' : 'en';

        $this->mailer->send('comment-notification', $locale, $recipient, [
            'commenter_name' => (string) $comment->comment_author,
            'commenter_email' => (string) $comment->comment_author_email,
            'comment_body' => (string) $comment->comment_content,
            'post_title' => get_the_title($post),
            'post_url' => (string) get_permalink($post),
            'approve_url' => $this->approveUrl((int) $comment->comment_ID),
            'submitted_at' => (string) get_comment_date('', $comment),
        ]);
    }

    /** A nonce-protected wp-admin approve link for the moderator. */
    private function approveUrl(int $commentId): string
    {
        $base = admin_url("comment.php?action=approvecomment&c={$commentId}");

        return html_entity_decode(wp_nonce_url($base, "approve-comment_{$commentId}"), ENT_QUOTES, 'UTF-8');
    }
}
