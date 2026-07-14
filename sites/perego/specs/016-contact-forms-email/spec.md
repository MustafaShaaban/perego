# Spec 016 — Contact, forms, form states, email routing

**Branch:** `feature/016-contact-forms-email`
**Mode:** Client Site Mode
**Status:** Complete (2026-07-14) — full lifecycle verified with real submissions; two AR i18n gaps fixed.
**Depends on:** 010 (CoreX runtime: Forms/Submissions/Data Models), 011 (shell/footer), 013 (contact preselect).

## Goal

The three site forms and the email/storage stack are **built** (specs 004–010). Spec 016 verifies the full
lifecycle end-to-end and closes any gap: each form editable + routed + validated + stored, all form states,
Email Studio routing, and **real submissions** — EN/AR — plus confirming Forms & Flows / Submissions / Data
Models are visible and functional in wp-admin.

## Authoritative source

`_design_handoff/.../site/contact.html` + form interactions; reference `perego-reference.scss`. CoreX public
seams for Forms/Submissions/Email (no framework edits; no private duplicates).

## Audit — what exists

| Piece | Where | Status |
|-------|-------|--------|
| Contact "project brief" form | `Forms\ProjectBriefForm` (CoreX Form) + `ContactServiceChooserRenderer` | built; `?service=` preselect (spec 013) |
| Footer quick-message form | `Forms\QuickMessageForm` (CoreX Form) | built (standard footer) |
| Careers / "Join us" form (CV upload) | `Careers\PeregoCareersController` (REST) + `join-form` block | built |
| Submission storage | CoreX submissions + `…corex_applications` (careers) | built (spec 010 verified id 150) |
| Email routing | `Email\PeregoFormMailListener` → `PeregoMailer` + `PeregoEmailRenderer` | built |
| Form states (default/invalid/submitting/success/server-error) | CoreX form runtime + `aria-live` in markup | present — verify each |

## Functional requirements

- **FR-1 All three forms editable** through CoreX Forms (fields/labels/routing) via the public seam — no
  hardcoded duplication; Forms & Flows / Submissions / Data Models visible + functional in wp-admin.
- **FR-2 Validation.** Client-side (required/type/length) + server-side (sanitise, nonce/honeypot, file-type
  for CV) — invalid input blocked with accessible messaging.
- **FR-3 All states rendered.** default → submitting → success, and invalid + server-error, each visible +
  announced (aria-live), matching the handoff interaction.
- **FR-4 Real submission stored.** A real submit on each form persists a Submission/record (visible in
  wp-admin) with the correct fields; careers CV attaches/stores.
- **FR-5 Email routed.** Each submission routes an email via Email Studio/`PeregoMailer` to the configured
  recipient with the rendered template; failures degrade to the server-error state (no data loss).
- **FR-6 EN/AR.** All of the above in both locales, RTL correct.

## Acceptance

- [x] Each of the three forms: real submit → stored record (wp-admin) + routed email; all states verified.
- [x] Validation (client + server) blocks bad input with accessible messaging; CV file-type enforced.
- [x] Forms & Flows / Submissions / Data Models visible + functional in wp-admin (spec 010 GAP-1/2 noted).
- [x] EN + AR, RTL; captured evidence (Playwright AR contact + inbox/forms/data-models screenshots).
- [x] Pest green (256/256); clean-code-guard + wp-guard pass for the JoinFormRenderer/GlobalContent/i18n change.

## Notes

Mostly verification + gap-fixing — the stack is built. Verify with **real submissions** (Playwright form
fills; then confirm the stored record via wp-cli and the mail via the mailer's captured output / a log), not
hidden DOM. Use the CoreX public seams only. Runtime admin: admin / password (never commit/log). Keep GAP-1
(no CPT DataModels seam) / GAP-2 (deploy builds CoreX admin assets) documented in `docs/corex-framework-gaps.md`.
