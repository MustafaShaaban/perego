# Contact "Start a Project" — visual-acceptance evidence (US2 / T016)

**Reference**: `_design_handoff/Perego-Creative-Studio-Final-Handoff/site/contact.html`
**Live**: EN `http://perego.local/contact/` · AR `http://perego.local/ar/contact-2/`
**Template**: `page.html`; the brief is the server-rendered `ProjectBriefForm` (CoreX Forms engine).

## Material differences found and resolved

| # | Section | Handoff | Live (before) | Disposition |
| --- | --- | --- | --- | --- |
| 1 | The whole form | Glass-card brief with underlined fields, a 2-column field grid, styled select + submit | **Completely unstyled** — raw browser inputs, a raw `<select multiple>`, an unstyled Send button. The CoreX Forms engine emits `.corex-form__*` markup, but the framework ships those styles **only as a Gutenberg-block stylesheet** (loads with the editor block, not the server-rendered form) and against framework tokens (`--ink`/`--surface`/`--primary`) the Perego dark theme doesn't define | **Resolved** — ported the handoff `.field`/`.field-row` treatment onto `.corex-form__*` in shared `main.scss` using Perego tokens (logical props, RTL-safe): a 12-col grid, underlined inputs, styled labels/required marks, select + multi-select, error/status states. Fixes **both** the contact brief and the footer "Send a quick message" form (both use `.corex-form`) |
| 2 | Field layout | Paired fields side by side (name\|email, phone\|company, budget\|subject) | All fields full-width, stacked | **Resolved** — set `'width' => 'half'` on the six paired fields in `ProjectBriefForm` (a supported CoreX Forms field key: full/half/third/two-thirds/quarter), so the engine emits `--half` and the client grid lays them 2-up |
| 3 | Visible honeypot | (hidden) | A raw white text input rendered above Send in **both** forms — the framework's off-screen `.corex-form__hp` rule lives in the block stylesheet that never loads, so the spam trap was visible and defeated | **Resolved** — added the off-screen `.corex-form__hp` rule to the client stylesheet (same technique as core's `.screen-reader-text`) |
| 4 | Submit button | Accent pill | Unstyled button | **Resolved** — extended the `.perego-btn` / `.perego-btn--accent` selector groups to include `.corex-form__submit` (reuses the exact button primitive, no duplicated declarations) |

## Deliberately deferred / adapted (documented)

- **Service chooser as toggle buttons** — the handoff renders "Choose your service" as toggle
  buttons; the engine's `multi-select` field type renders a native `<select multiple>`. The native
  control is styled, functional, and accessible; toggle-button chips would need a new framework field
  type (out of Client-Site-Mode scope). Documented, not silently skipped.
- **Two-column split panel** — the handoff hero splits the form (left) from the service chooser
  (right) into two page columns. Our services field lives inside the single form, so the form is one
  centered column with 2-up field rows — a faithful adaptation of the same content.

## Cross-cutting launch blocker discovered (NOT a contact-only issue)

**UI-string i18n is not wired.** On every AR route, the header nav ("Home/About Us/…"), form field
labels, and button text render in **English**, because there are no `.po`/`.mo` translations and no
`pll_register_string` calls for the `perego-site`/theme text domains — so every `__(…, 'perego-site')`
string is English regardless of locale. Verified present on the already-accepted AR home page (not
introduced here). The handoff is English-only so this is not a deviation *from the handoff*, but it is
a real bilingual-launch blocker. Tracked for Phase 5 (T019/T021); it needs its own i18n slice
(gettext `.po`/`.mo` or Polylang string translations), not a per-route fix.

## Verification

72-check route-health (0 fails, incl. EN/AR desktop+mobile contact), 12-page a11y (0 serious/critical,
incl. contact en + ar), 4/4 interactions, 195 Pest. EN + AR captures confirm correct RTL mirroring.
