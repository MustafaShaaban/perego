# Perego — Final Completion Roadmap (durable program memory)

> Authoritative program plan for finishing Perego. A new session can resume from this file plus
> `PROGRESS.md` (§RESUME HERE) without relying on chat history. Update it continuously.
> Last updated: 2026-07-14.

## Program objective

Finish the Perego website to be (a) **visually identical to the locked Creative Studio final handoff**
(`_design_handoff/Perego-Creative-Studio-Final-Handoff/site/`) and (b) **genuinely manageable** through
the WordPress Site Editor, the native post editors, and CoreX (Forms, Submissions, Data Models, Email
Studio) — no hidden CPTs, seed-only content, or hard-coded editorial prose standing in for real editing.

## Locked architecture decisions

1. **Block-first global content.** Header, navigation, footers, footer quick-message, careers area,
   global CTA, homepage sections, 404 copy, generic/legal/search layouts are real FSE template parts or
   editable blocks with a meaningful Site-Editor preview. No "edit another record" placeholder.
2. **Remove the `perego_section` (Global Sections) CPT** after a safe, idempotent, dry-run-gated
   migration. Do not replace it with another hidden CPT or an options blob.
3. **Allowed collection content types only:** native posts (Journal), `perego_project`, `perego_service`,
   `perego_client`. Everything else global is blocks/template-parts.
4. **Structured metadata** registered explicitly via WP metadata APIs + CoreX contracts (REST schema,
   sanitization, auth, editor UI, validation, translation). No ACF hard dependency; no generic
   Custom-Fields reliance; no seed-only `update_post_meta` as the primary path.
5. **CoreX owns Forms/Submissions/Data Models/Email Studio.** Perego binds to it via public extension
   seams; no duplicate Perego admin pages for CoreX-owned functionality.
6. **One visual authority:** the handoff stylesheet, tokenised where it doesn't change the result. Logical
   CSS for RTL; local fonts; no external CSS frameworks; no route-specific inline patches.
7. **Bilingual (EN/AR)** via Polylang Free where it already drives content; global editable blocks store
   EN+AR and render only the current language (never both in the DOM hidden by CSS).

## Deployment identity (freeze)

- **Repo root:** `C:\wamp64\www\perego` (single checkout; no worktrees). Remotes: `origin` =
  `MustafaShaaban/perego`; `upstream` = CoreX (**push disabled — never push upstream**).
- **Program branch line:** `feature/008-visual-fidelity-recovery` (recovery work, unmerged, T009/T010
  open). Spec 009 branches off its tip as `feature/009-cms-block-architecture-cleanup`.
- **Live site:** `https://mower-hamstring-baggy.ngrok-free.dev/` (admin creds provided out-of-band for
  runtime verification only — never committed).
- **Deployed commit identity: NOT YET PROVEN.** Confirming which commit the ngrok site serves requires
  runtime access (a build fingerprint / admin check). Until proven, do not assume the live site matches
  any audited branch. **First runtime step:** capture the deployed commit/asset fingerprint.

## Feature specs in execution order (one feature = one branch = one PR)

| Spec | Scope | Branch | Status |
|------|-------|--------|--------|
| 009 | CMS block architecture + Global Sections migration/removal + cleanup | `feature/009-cms-block-architecture-cleanup` | **IN PROGRESS** (foundation authored) |
| 010 | CoreX runtime, Forms, Submissions, Data Models | `feature/010-corex-runtime-content-models` | Not started |
| 011 | Global shell + preloader | `feature/011-global-shell-preloader` | Not started |
| 012 | Homepage design + functional completion | `feature/012-home-page-fidelity` | Not started |
| 013 | Services archive + four service singles | `feature/013-services-pages-completion` | Not started |
| 014 | Work archive + project single | `feature/014-work-project-pages` | Not started |
| 015 | Journal, single article, search | `feature/015-journal-search-pages` | Not started |
| 016 | Contact, forms, form states, email routing | `feature/016-contact-forms-email` | Not started |
| 017 | Generic page, terms, privacy, 404 | `feature/017-supporting-pages` | Not started |
| 018 | Final visual acceptance, cleanup, release | `feature/018-final-acceptance-release` | Not started |

**Dependencies:** 009 (architecture) is the foundation for 011/012/017 (which edit the now-block-first
global areas). 010 (CoreX runtime + forms) is a hard dependency for 016 (contact/forms/email) and is
independent enough to run in parallel with 011–015. 018 is last and depends on all.

## Global Sections inventory (verified 2026-07-14, static)

**Code footprint (all under `sites/perego/perego-site/`):**
- `src/PostTypes/GlobalSectionPostType.php` — `perego_section` CPT + `_perego_section_role` meta.
- `src/Content/GlobalSectionResolver.php` — pure role+locale → id resolver (no-language-mixing rule).
- `src/Blocks/GlobalSectionRenderer.php` — server render of the current-language record.
- `src/Blocks/global-section/{block.json,index.js,style.scss}` — the `perego-theme/global-section` block.
- `scripts/seed-global-sections.php` — idempotent EN/AR seed of the roles below.
- `src/PeregoSiteServiceProvider.php` — `registerGlobalSections()` + `perego_section` listed in
  `registerTranslatablePostTypes()`.
- Tests: `tests/PostTypes/GlobalSectionPostTypeTest.php`, `tests/Content/GlobalSectionResolverTest.php`,
  `tests/Blocks/GlobalSectionRenderTest.php`.

**Roles seeded (7 × EN/AR = 14 intended records; live count needs runtime confirmation):**
`header`, `standard-footer`, `contact-footer`, `footer-careers`, `global-cta`, `contact-details`,
`not-found`. (Note: `footer-careers` is seeded but is **not** in `GlobalSectionPostType::ROLES` — a
latent inconsistency.)

**Consumption — the key de-risking finding:**
- **Only `footer-careers` is rendered on the public frontend**, via
  `SiteFooterRenderer::careersEditorial()` → `wp:perego-theme/global-section {"role":"footer-careers"}`.
  This is the footer "Join us" heading + blurb.
- The block is **not referenced by any FSE template or template part** (`parts/header.html`,
  `parts/footer.html`, `parts/footer-flat.html` render the PHP `site-header`/`site-footer` blocks).
- The other **6 roles are seeded but unconsumed** — the actual header/footer/CTA/404 copy renders from PHP
  providers (`SiteHeaderRenderer`, `SiteFooterRenderer`, `PeregoSite\Content\GlobalContent`), so removing
  them causes **no frontend content loss**.

**Migration implication:** the only content that must be given a new editable home before removal is the
`footer-careers` EN/AR editorial. Everything else is a safe code+record removal once verified.

## FSE templates & parts inventory (verified 2026-07-14)

- **Templates (13):** `404`, `archive-perego_project`, `archive-perego_service`, `front-page`, `home`,
  `index`, `legal`, `page`, `page-contact`, `search`, `single`, `single-perego_project`,
  `single-perego_service`.
- **Parts (3):** `header` (`site-header` block), `footer` (`site-footer` + `media-lightbox`),
  `footer-flat`.

## Content-model inventory (target)

Collections: Journal (native posts), `perego_project`, `perego_service`, `perego_client`. Global content:
FSE parts/blocks. See spec 010 for the full field lists per model.

## CoreX integration inventory (to verify at runtime — spec 010)

Required admin routes: Overview, Forms & Flows, Submissions, Data Models, Data, Email Studio, Access &
Abilities, Operations & Security, Insights, Blog Pro (where enabled). Required flows: footer quick message,
project brief/contact, careers/join. **Live dashboard currently reported as not exposing expected
Forms/Submissions/Data Models** — treat as a release-blocking runtime/deployment problem (spec 010).

## Database migration inventory

| Migration | Purpose | Mode | Status |
|-----------|---------|------|--------|
| Global Sections → block-first | Move `footer-careers` EN/AR into an editable footer surface; inventory+report other roles; remove `perego_section` posts/meta/translation relations | dry-run first, backup-gated, idempotent | **designed (spec 009); not executed — needs runtime + backup gate** |

## Visual acceptance matrix

Widths 320/375/430/768/1024/1280/1440/wide × EN-LTR + AR-RTL × required states, per page. Tracked in spec
018 and each page spec's evidence. Automated capture (`capture-visual-recovery.mjs`) proves overflow/errors
only — **manual overlay/diff review is required for acceptance**.

## Completed items

- Startup audit + static inventory of Global Sections and FSE templates (this file, 2026-07-14).
- Spec 008 recovery work (unmerged): T001–T008 substantially closed; see `PROGRESS.md`.

## Remaining items

- Spec 009: execute dry-run migration, backup gate, migrate `footer-careers`, remove CPT + dead code,
  cleanup report, prove FSE editability of migrated global areas. Then specs 010–018 in order.

## Known blockers

1. **Deployed-commit identity unproven** — needs runtime access to fingerprint the live build.
2. **Migration execution blocked** on a runtime DB backup gate (cannot back up the live DB from this repo).
3. **T009 WP editor "no recovery warning" check** — earlier blocked on a dev credential that stopped
   working; ngrok admin creds now provided for runtime verification.
4. **CoreX admin (Forms/Submissions/Data Models) reportedly missing on live** — spec 010 runtime problem.

## Exact recommended next action

Author is complete for the spec 009 foundation (this roadmap + `specs/009-.../{spec,plan,tasks}.md`).
**Next concrete step:** implement the *dry-run* migration reporter script
(`scripts/migrate-global-sections.php --dry-run`) that inventories every live `perego_section` EN/AR
record, classifies each role as consumed/unconsumed, and maps `footer-careers` to its new editable home —
**report only, zero writes** — then run it against the live DB behind the backup gate before any removal.
