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
 * On a Perego form submission, sends the submitter their branded confirmation (spec Phase 7/8):
 * the footer quick-message → contact-confirmation, the Start-a-Project brief → project-brief-
 * confirmation. Rendered in the visitor's current language and delivered through PeregoMailer.
 *
 * The submitter is never a recipient of the engine's own SendEmailListener (which notifies the
 * admin inbox), so this adds the user-facing email without duplicating the internal one. Unknown
 * form slugs and missing emails are ignored — non-fatal on the submission path.
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

    public function __construct(private readonly PeregoMailer $mailer)
    {
    }

    public function __invoke(FormSubmittedEvent $event): void
    {
        $values = $event->values;
        $email = isset($values['email']) ? (string) $values['email'] : '';
        if ($email === '') {
            return;
        }

        $locale = $this->currentLocale();

        match ($event->formSlug) {
            QuickMessageForm::SLUG => $this->mailer->send('contact-confirmation', $locale, $email, [
                'name' => (string) ($values['name'] ?? ''),
                'email' => $email,
                'message' => (string) ($values['message'] ?? ''),
            ], $email),
            ProjectBriefForm::SLUG => $this->mailer->send('project-brief-confirmation', $locale, $email, [
                'name' => (string) ($values['name'] ?? ''),
                'email' => $email,
                'phone' => (string) ($values['phone'] ?? ''),
                'company' => (string) ($values['company'] ?? ''),
                'services' => $this->formatServices($values['services'] ?? ''),
                'budget' => $this->budgetLabel((string) ($values['budget'] ?? '')),
                'subject' => (string) ($values['subject'] ?? ''),
                'message' => (string) ($values['message'] ?? ''),
            ], $email),
            default => null,
        };
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
