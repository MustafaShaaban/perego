# Spec 016 — Tasks

- [x] T001 **Audit — the forms/email/storage stack is built.** Three forms: `Forms\ProjectBriefForm`
    (contact brief + service chooser, `?service=` preselect), `Forms\QuickMessageForm` (footer), and the
    careers/"Join us" form (`Careers\PeregoCareersController` REST + `join-form` block, CV upload). Storage via
    CoreX submissions + `…corex_applications`; email via `Email\PeregoFormMailListener` → `PeregoMailer` +
    `PeregoEmailRenderer`; states carry `aria-live`. Spec 010 verified a real submission (id 150). Recorded in
    spec.md. Spec 016 = lifecycle verification + gap-fixing.
- [x] T002 **Forms editable via CoreX seams.** Verified live (Playwright, admin): **Submissions** inbox React
    app renders "6 accessible submissions" with full Search/Flow ID/Status/Owner/date filters, a
    SUBMITTER/FLOW/STATUS/OWNER/RECEIVED table listing both flows (`perego-project-brief`,
    `perego-quick-message`), and Privacy-Operations retention. **Forms & Flows** shows a functional flow
    builder (name/slug/description/Create draft + lifecycle filter). **Data Models** shows the "Form
    submissions" schema with every field (name/email/message/services/phone/company/budget/subject; types
    text/email/textarea/tel) plus Records/Import/Export/Migrations, capability-backed ("Allowed"). Forms are
    code-registered CoreX `Form` classes (public seam) captured under flow slugs — GAP-1 documented.
- [x] T003 **Validation.** Client-side verified in served HTML: `aria-required` (×3/form), `required`,
    `type="email"`, `maxlength="120"/"80"`. Server-side verified in code: CoreX rules (`required|email|max`),
    sanitisers (`sanitize_text_field`/`sanitize_email`), honeypots (`corex_hp`, `perego_hp`), and careers
    `validateCv` (size + MIME + extension + finfo sniff) returning accessible `fail()` states. Invalid input
    blocked.
- [x] T004 **Form states.** Success proven with a REAL Playwright submit → "✓ Thank you — your message has
    been sent." Invalid + server-error proven in code as `fail()` returning 422/429/500 with the
    `aria-live="polite"` status region present in all three forms' markup.
- [x] T005 **Real submission stored.** Real submits persisted: quick-message id 159, brief id 150 (fields
    incl. `corex_field_services=graphic-design`); careers `record()` → `…corex_applications` + private CV
    attachment. Inbox shows 6 submissions across both flows. All spec-016 test records cleaned up
    (`example.test`: 0 remaining).
- [x] T006 **Email routed.** Proven by dispatching `FormSubmittedEvent` and capturing mail via `pre_wp_mail`:
    quick-message → 2 mails (admin "New contact form submission" + "Thanks for reaching out — Perego");
    brief → 2 mails (admin + "We got your project brief — Perego"); careers `notify()` uses the same
    `PeregoMailer`. Failures degrade to the server-error state (no data loss).
- [x] T007 **EN/AR + RTL.** AR contact renders `<html dir="rtl" lang="ar">`; project-brief fully AR
    (placeholders + labels). **Fixed two real i18n gaps:** the join form's hardcoded EN placeholders (now
    `GlobalContent` `namePlaceholder`/`portfolioPlaceholder`, AR + EN) and the missing AR "Select a range" /
    "Say hello." gettext (added to `-ar.po`, recompiled `.mo`). Re-verified live: no EN placeholders remain,
    budget default reads "اختر نطاقًا".
- [x] T008 Suite + guards; update durable memory; open PR. Pest 256/256 (796 assertions);
    clean-code-guard + wp-guard pass (placeholders escaped via `esc_attr`, i18n complete, no new
    query/AJAX/REST). PROGRESS/roadmap/DECISIONS updated. PR opened.
