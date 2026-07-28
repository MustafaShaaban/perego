<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Forms;

defined('ABSPATH') || exit;

use Corex\Forms\Form;
use Corex\Forms\Listeners\StoreSubmissionListener;

/**
 * The footer "quick message" form (spec Phase 7, form 1) — the lightweight contact entry point
 * rendered in every footer's quick-message column. Name, email, and message, each validated both
 * client- and server-side by the shared CoreX Forms engine (schema resolver → validator → submission
 * pipeline: honeypot/captcha protection, storage, routing, email notification). Labels are
 * translation-ready (resolved at call time). Ported from the handoff footer "Send a quick message".
 */
final class QuickMessageForm extends Form
{
    public const SLUG = 'perego-quick-message';

    public string $slug = self::SLUG;

    public function label(): string
    {
        return __('Quick message', 'perego-site');
    }

    /**
     * Storage only — the engine's SendEmailListener is deliberately dropped.
     *
     * It builds a `label: value` plain-text body that WpMailDriver then delivers as `text/html`, so the
     * team notification arrived as one unformatted run with no `Reply-To` (client report 2026-07-27).
     * PeregoFormMailListener sends the branded notification instead; leaving both registered would send
     * the team two emails per submission.
     *
     * @return list<class-string>
     */
    public function listeners(): array
    {
        return [StoreSubmissionListener::class];
    }

    /**
     * @return array<string,array{type:string,rules:list<string>,label:string,placeholder:string}>
     */
    public function fields(): array
    {
        return [
            'name' => [
                'type' => 'text',
                'rules' => ['required', 'max:120'],
                'label' => __('Full name', 'perego-site'),
                'placeholder' => __('Put your name here', 'perego-site'),
            ],
            'email' => [
                'type' => 'email',
                'rules' => ['required', 'email', 'max:190'],
                'label' => __('E-mail', 'perego-site'),
                'placeholder' => __('Put your E-mail here', 'perego-site'),
            ],
            'message' => [
                'type' => 'textarea',
                // 200 words ≈ the handoff's original 1200-character cap (spec 020 D2) at typical
                // English/Arabic prose density — a word budget reads more meaningfully to a writer
                // than a character count (spec 020 round 4). `data-max-words` (not `maxlength`,
                // which is inherently character-based and would silently cap input below the word
                // limit) is the live counter's (site-footer/view.js) source of truth for the limit.
                'rules' => ['required', 'max_words:200'],
                'label' => __('Your message', 'perego-site'),
                'placeholder' => __('Say hello.', 'perego-site'),
                'attrs' => ['data-max-words' => '200'],
            ],
        ];
    }
}
