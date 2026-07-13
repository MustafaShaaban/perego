# Tasks: 005 — UI-string i18n (EN/AR gettext)

- [x] T001 Spec + decision (gettext `.po`/`.mo`, not Polylang strings — constitution IX). `spec.md`.
- [x] T002 Wrap the hardcoded `SiteHeaderRenderer` nav labels (+ Services dropdown) in `__()` with the
  literal `perego-site` domain; moved from a `const` to a `navItems()` method so they are both
  make-pot-extractable and translated at render time.
- [x] T003 Load the `perego-site` textdomain on `init` from `perego-site/languages/`; add `Domain Path`
  to the plugin header (`perego-site.php`).
- [x] T004 Generate `languages/perego-site.pot` (`wp i18n make-pot`, 152 strings); author
  `perego-site-ar.po` for every public-facing UI string (nav, forms, buttons, aria, slider controls),
  compile `perego-site-ar.mo` (`wp i18n make-mo`). Admin/editor-only strings intentionally left to
  English fallback (never surface on public routes — see spec non-goals).
- [x] T005 Localize the framework (`corex` domain) public form strings — submit label + success/error
  status — via a client-side `gettext_corex` filter (`I18n/FrameworkFormStrings`); no framework code
  touched. Unit-tested (5 Pest cases).
- [x] T006 Verify: AR routes render Arabic nav + form labels + buttons + status; EN unchanged; gates
  green (Pest 200 · Jest 67 · route-health 72/0 · a11y 12/0 · interactions 4/4). AR home header + AR
  contact form confirmed via live capture.

## Result

The top launch blocker from spec 004 is closed. Every public AR route now shows Arabic UI chrome. See
`../004-design-fidelity/content-manifest.md` (blocker #1 marked resolved).

## Follow-ups (not blocking)

- Admin/editor `perego-site` + `corex` strings are English in wp-admin only (out of scope).
- If more framework (`corex`) strings later surface on public routes, extend `FrameworkFormStrings::AR`.
