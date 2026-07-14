# Spec 016 — Tasks

- [x] T001 **Audit — the forms/email/storage stack is built.** Three forms: `Forms\ProjectBriefForm`
    (contact brief + service chooser, `?service=` preselect), `Forms\QuickMessageForm` (footer), and the
    careers/"Join us" form (`Careers\PeregoCareersController` REST + `join-form` block, CV upload). Storage via
    CoreX submissions + `…corex_applications`; email via `Email\PeregoFormMailListener` → `PeregoMailer` +
    `PeregoEmailRenderer`; states carry `aria-live`. Spec 010 verified a real submission (id 150). Recorded in
    spec.md. Spec 016 = lifecycle verification + gap-fixing.
- [ ] T002 **Forms editable via CoreX seams.** Confirm Forms & Flows / Submissions / Data Models are visible +
    functional in wp-admin (real screens, not just REST); each form's fields/labels/routing editable through
    the public seam. Note GAP-1/2 status.
- [ ] T003 **Validation.** Verify client-side (required/type/length) + server-side (sanitise, nonce/honeypot,
    CV file-type) on all three forms; invalid input blocked with accessible messaging.
- [ ] T004 **Form states.** Real interaction proof of default → submitting → success, plus invalid and
    server-error, each visible + `aria-live`-announced, per the handoff. Capture EN + AR.
- [ ] T005 **Real submission stored.** Submit each form for real → a Submission/record persists (verify in
    wp-admin / wp-cli) with correct fields; careers CV stored. Clean up test records.
- [ ] T006 **Email routed.** Each submission routes an email via the mailer to the configured recipient with
    the rendered template; server-error degrades safely (no data loss). Verify via captured mail/log.
- [ ] T007 **EN/AR + RTL** across all forms/states.
- [ ] T008 Suite + guards; update durable memory; open PR.
