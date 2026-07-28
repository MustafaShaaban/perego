<?php

/**
 * @package PeregoSite
 */

declare(strict_types=1);

namespace PeregoSite\Email;

defined('ABSPATH') || exit;

use Corex\Forms\Submission\FormSubmittedEvent;
use PeregoSite\Forms\ProjectBriefForm;
use PeregoSite\Forms\QuickMessageForm;

/**
 * On a Perego form submission, sends both branded emails (spec Phase 7/8): the submitter's
 * confirmation — footer quick-message → contact-confirmation, Start-a-Project brief →
 * project-brief-confirmation — and the team's notification, rendered in the visitor's current
 * language and delivered through PeregoMailer.
 *
 * The team notification is sent here rather than left to the engine's own SendEmailListener. That
 * listener builds a `label: value` plain-text body (NotificationDispatcher::plainTextBody) which
 * WpMailDriver then delivers with `Content-Type: text/html`, so every line collapses into one
 * unformatted run — the "notifications arrive with no template" report of 2026-07-27. Routing it
 * through the branded `admin-notification` template fixes the design AND gives the team a real
 * `Reply-To` pointing at the submitter, which the engine path never set.
 *
 * Unknown form slugs and missing emails are ignored — non-fatal on the submission path.
 */
final class PeregoFormMailListener
{
    private const BUDGET_LABELS = [
        'under-1k' => 'Under $1,000',
        '1k-5k' => '$1,000 – $5,000',
        '5k-15k' => '$5,000 – $15,000',
        '15k-plus' => '$15,000+',
        'not-sure' => 'Not sure yet',
    ];

    public function __construct(
        private readonly PeregoMailer $mailer,
        private readonly TeamRecipient $recipient,
    ) {
    }

    public function __invoke(FormSubmittedEvent $event): void
    {
        $values = $event->values;
        $email = isset($values['email']) ? (string) $values['email'] : '';
        if ($email === '') {
            return;
        }

        $locale = $this->currentLocale();

        // No Reply-To on the confirmations: they go TO the submitter, so passing their own address
        // made "Reply" answer themselves. PeregoMailer substitutes the no-reply mailbox.
        match ($event->formSlug) {
            QuickMessageForm::SLUG => $this->mailer->send('contact-confirmation', $locale, $email, [
                'name' => (string) ($values['name'] ?? ''),
                'email' => $email,
                'message' => (string) ($values['message'] ?? ''),
            ]),
            ProjectBriefForm::SLUG => $this->mailer->send('project-brief-confirmation', $locale, $email, [
                'name' => (string) ($values['name'] ?? ''),
                'email' => $email,
                'phone' => (string) ($values['phone'] ?? ''),
                'company' => (string) ($values['company'] ?? ''),
                'services' => $this->formatServices($values['services'] ?? ''),
                'budget' => $this->budgetLabel((string) ($values['budget'] ?? '')),
                'subject' => (string) ($values['subject'] ?? ''),
                'message' => (string) ($values['message'] ?? ''),
            ]),
            default => null,
        };

        $this->notifyTeam($event->formSlug, $locale, $email, $values);
    }

    /**
     * The branded internal notification — the one email that keeps `Reply-To` = the submitter, so the
     * team can answer a client by hitting Reply. Recipient comes from {@see TeamRecipient}.
     *
     * @param array<string, mixed> $values
     */
    private function notifyTeam(string $formSlug, string $locale, string $email, array $values): void
    {
        $formName = match ($formSlug) {
            QuickMessageForm::SLUG => 'Contact',
            ProjectBriefForm::SLUG => 'Project brief',
            default => '',
        };

        $recipient = $this->recipient->address();
        if ($formName === '' || $recipient === '') {
            return;
        }

        $this->mailer->send('admin-notification', $locale, $recipient, [
            'form_name' => $formName,
            'submitted_at' => (string) current_time('mysql'),
            'reply_email' => $email,
            'fields_html' => PeregoEmailRenderer::fieldRows($this->teamFields($formSlug, $email, $values)),
        ], $email);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, string>
     */
    private function teamFields(string $formSlug, string $email, array $values): array
    {
        $optional = static fn (string $v): string => $v !== '' ? $v : '—';

        $fields = [
            'Name' => $optional((string) ($values['name'] ?? '')),
            'Email' => $email,
        ];

        if ($formSlug === ProjectBriefForm::SLUG) {
            $fields['Phone'] = $optional((string) ($values['phone'] ?? ''));
            $fields['Company'] = $optional((string) ($values['company'] ?? ''));
            $fields['Service(s)'] = $optional($this->formatServices($values['services'] ?? ''));
            $fields['Budget'] = $optional($this->budgetLabel((string) ($values['budget'] ?? '')));
            $fields['Subject'] = $optional((string) ($values['subject'] ?? ''));
        }

        $fields['Message'] = $optional((string) ($values['message'] ?? ''));

        return $fields;
    }

    /** Human-readable service list from the multi-select's slugs (array or comma string). */
    private function formatServices(mixed $services): string
    {
        $items = is_array($services) ? $services : array_filter(array_map('trim', explode(',', (string) $services)));

        $names = array_map(
            static fn (string $slug): string => ucwords(str_replace('-', ' ', $slug)),
            array_map('strval', $items),
        );

        return implode(', ', $names);
    }

    private function budgetLabel(string $value): string
    {
        return self::BUDGET_LABELS[$value] ?? $value;
    }

    private function currentLocale(): string
    {
        if (function_exists('pll_current_language')) {
            $lang = (string) pll_current_language();
            if ($lang !== '') {
                return $lang;
            }
        }

        return str_starts_with((string) get_locale(), 'ar') ? 'ar' : 'en';
    }
}
