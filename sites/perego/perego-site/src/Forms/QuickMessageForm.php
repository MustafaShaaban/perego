<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Forms;

defined('ABSPATH') || exit;

use Corex\Forms\Form;

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
                'rules' => ['required', 'max:2000'],
                'label' => __('Your message', 'perego-site'),
                'placeholder' => __('Say hello.', 'perego-site'),
            ],
        ];
    }
}
