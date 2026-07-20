<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Blocks;

defined('ABSPATH') || exit;

/**
 * Server-renders the perego-theme/journal-comments block: the journal single's comments section in
 * the handoff's exact markup (single-post.html:110-146) — the approved-comment list (`.comment-list`
 * of initials-avatar `.comment` cards, indented `.comment--reply`) plus the "Leave a comment" form
 * (`.comment-form.page-form`). WordPress's own comment blocks emit gravatar-image cards and a
 * different form, so this is a custom renderer; the reference `.comment*`/`.comment-form`/`.field`
 * styles apply verbatim (perego-reference.scss).
 *
 * The form posts to the secure `perego/v1/comments` REST endpoint via view.js (AJAX, with a
 * thank-you / awaiting-review message and on-the-fly insertion of approved comments), and degrades to
 * a plain `wp-comments-post.php` POST without JS. Reply is handled in view.js (a "Replying to X" chip
 * that just sets `comment_parent`), not WordPress's `comment-reply.js` form-moving dance.
 *
 * `render()`/`renderCard()` take already-resolved data so they stay pure, unit-testable functions of
 * their inputs; the block's render callback does the get_comments() query and the REST controller
 * reuses `renderCard()` to render a freshly-approved comment for the client to inject.
 */
final class JournalCommentsRenderer
{
    /**
     * @param list<array{id?: int, author: string, date: string, text: string, isReply: bool}> $comments
     * @param array<string, mixed> $ctx localized strings + endpoint/nonce/postId (see the block callback)
     */
    public function render(array $comments, array $ctx): string
    {
        $count = count($comments);
        $titleFormat = $count === 1 ? (string) $ctx['titleOne'] : (string) $ctx['title'];

        $endpoint = (string) ($ctx['endpoint'] ?? '');
        $nonce = (string) ($ctx['nonce'] ?? '');
        $messages = (string) ($ctx['messagesJson'] ?? '{}');

        $html = '<section class="comments" data-perego-comments '
            . 'data-endpoint="' . esc_url($endpoint) . '" '
            . 'data-nonce="' . esc_attr($nonce) . '" '
            . "data-messages='" . esc_attr($messages) . "'>";

        if ($count > 0) {
            $html .= '<h2 class="comments__title">' . esc_html(sprintf($titleFormat, $count)) . '</h2>';
            $html .= '<ul class="comment-list">';
            foreach ($comments as $comment) {
                $html .= $this->renderCard($comment, $ctx);
            }
            $html .= '</ul>';
        }

        $html .= $this->renderForm($ctx);
        $html .= '</section>';

        return $html;
    }

    /**
     * One comment card in the handoff's markup — an initials avatar + a bordered body (name/date head,
     * text, Reply link). Public so the REST controller can render a freshly-approved comment for the
     * client to insert without a reload. `isReply` adds the handoff's indented `.comment--reply`.
     *
     * @param array{id?: int, author: string, date: string, text: string, isReply: bool} $comment
     * @param array<string, mixed> $ctx
     */
    public function renderCard(array $comment, array $ctx): string
    {
        $id = (int) ($comment['id'] ?? 0);
        $classes = 'comment' . ($comment['isReply'] ? ' comment--reply' : '');
        $idAttr = $id > 0 ? ' id="comment-' . $id . '"' : '';

        $html = '<li' . $idAttr . ' class="' . esc_attr($classes) . '">';
        $html .= '<span class="comment__avatar" aria-hidden="true">'
            . esc_html($this->initials($comment['author'])) . '</span>';
        $html .= '<div class="comment__body">';
        $html .= '<div class="comment__head">';
        $html .= '<span class="comment__name">' . esc_html($comment['author']) . '</span>';
        $html .= '<span class="comment__date">' . esc_html($comment['date']) . '</span>';
        $html .= '</div>';
        $html .= '<p class="comment__text">' . esc_html($comment['text']) . '</p>';
        $html .= $this->replyLink($id, $comment['author'], (string) ($ctx['reply'] ?? 'Reply'));
        $html .= '</div>';
        $html .= '</li>';

        return $html;
    }

    /**
     * The "Reply" affordance. view.js reads `data-parent-id`/`data-parent-name` to set the form's
     * `comment_parent` and show the "Replying to X" chip (no DOM moving). `href="#respond"` is the
     * no-JS fallback (scrolls to the form). Degrades to a plain link when the comment id is unknown.
     */
    private function replyLink(int $commentId, string $author, string $replyLabel): string
    {
        if ($commentId <= 0) {
            return '<a class="comment__reply" href="#respond">' . esc_html($replyLabel) . '</a>';
        }

        return '<a class="comment__reply" href="#respond" '
            . 'data-parent-id="' . esc_attr((string) $commentId) . '" '
            . 'data-parent-name="' . esc_attr($author) . '">'
            . esc_html($replyLabel) . '</a>';
    }

    /**
     * The handoff "Leave a comment" form (single-post.html:138-145): Name + E-mail on one row, then
     * Comment. Posts to wp-comments-post.php by default (works without JS); view.js upgrades it to the
     * AJAX endpoint. Carries a honeypot + the post-id/parent hidden fields the endpoint needs.
     *
     * @param array<string, mixed> $ctx
     */
    private function renderForm(array $ctx): string
    {
        $postId = (int) ($ctx['postId'] ?? 0);
        $action = esc_url((string) ($ctx['commentsPostUrl'] ?? ''));

        $html = '<div id="respond" class="comment-respond">';
        $html .= '<form class="comment-form page-form" id="commentform" method="post" action="' . $action . '" novalidate>';
        $html .= '<h3 class="comment-form__title">' . esc_html((string) ($ctx['leaveComment'] ?? 'Leave a comment')) . '</h3>';

        // Reply-context chip (view.js reveals it and fills the name when replying).
        $html .= '<p class="comment-form__reply-chip" hidden>'
            . '<span>' . esc_html((string) ($ctx['replyingTo'] ?? 'Replying to')) . ' '
            . '<b class="comment-form__reply-name"></b></span> '
            . '<button type="button" class="comment-form__cancel">'
            . esc_html((string) ($ctx['cancel'] ?? 'Cancel')) . '</button></p>';

        $html .= '<div class="field-row">';
        $html .= '<div class="field"><label for="cm-author">' . esc_html((string) ($ctx['nameLabel'] ?? 'Name')) . '</label>'
            . '<input type="text" id="cm-author" name="author" autocomplete="name" '
            . 'placeholder="' . esc_attr((string) ($ctx['namePlaceholder'] ?? '')) . '" required /></div>';
        $html .= '<div class="field"><label for="cm-email">' . esc_html((string) ($ctx['emailLabel'] ?? 'E-mail'))
            . ' <span class="field__note">' . esc_html((string) ($ctx['emailNote'] ?? '')) . '</span></label>'
            . '<input type="email" id="cm-email" name="email" autocomplete="email" '
            . 'placeholder="' . esc_attr((string) ($ctx['emailPlaceholder'] ?? '')) . '" required /></div>';
        $html .= '</div>';

        $html .= '<div class="field"><label for="cm-comment">' . esc_html((string) ($ctx['commentLabel'] ?? 'Comment')) . '</label>'
            . '<textarea id="cm-comment" name="comment" rows="4" '
            . 'placeholder="' . esc_attr((string) ($ctx['commentPlaceholder'] ?? '')) . '" required></textarea></div>';

        // Honeypot — a real person never fills this; the endpoint rejects a non-empty value.
        $html .= '<div class="comment-form__hp" aria-hidden="true">'
            . '<label>' . esc_html((string) ($ctx['hpLabel'] ?? 'Leave this field empty'))
            . '<input type="text" name="perego_hp" tabindex="-1" autocomplete="off" /></label></div>';

        $html .= '<input type="hidden" name="comment_post_ID" value="' . esc_attr((string) $postId) . '" />';
        $html .= '<input type="hidden" name="comment_parent" value="0" />';

        $html .= '<p class="comment-form__status" role="status" aria-live="polite"></p>';
        $html .= '<button type="submit" class="btn btn--accent comment-form__submit">'
            . esc_html((string) ($ctx['submit'] ?? 'Post comment')) . '</button>';

        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Up to two uppercase initials from an author name ("Sara Adel" → "SA", "Karim" → "K"), for the
     * handoff's gradient-circle avatar. Falls back to "?" for an empty name.
     */
    private function initials(string $author): string
    {
        $words = preg_split('/\s+/', trim($author), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '?';
        }

        $first = function_exists('mb_substr') ? mb_substr($words[0], 0, 1) : substr($words[0], 0, 1);
        $second = '';
        if (count($words) > 1) {
            $last = $words[count($words) - 1];
            $second = function_exists('mb_substr') ? mb_substr($last, 0, 1) : substr($last, 0, 1);
        }

        $initials = $first . $second;

        return function_exists('mb_strtoupper') ? mb_strtoupper($initials) : strtoupper($initials);
    }
}
