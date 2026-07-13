# Spec 005 — UI-string i18n (EN/AR gettext)

**Branch**: `feature/005-i18n-strings` · **Mode**: Client Site Mode · **Depends on**: 004 (merged/open PR #10)

## Problem

On every AR route the header nav, form field labels, buttons, and aria-labels render in **English**.
Root causes (verified in spec 004, see `../004-design-fidelity/content-manifest.md`):
1. The `perego-site` plugin declares `Text Domain: perego-site` but **never loads** a translation file,
   so all 84 `__()`/`esc_html__()`/… calls fall through to their English source on `/ar/`.
2. `SiteHeaderRenderer` nav labels are **hardcoded English** (not `__()`-wrapped) — they cannot
   translate even once translations exist.

## Decision: gettext `.po`/`.mo`, not Polylang string translations

The constitution forbids Polylang as a hard dependency (Principle IX). UI-string translation must work
without Polylang's string tables, so we use standard WordPress gettext: a `perego-site-ar.mo` loaded via
`load_plugin_textdomain`. Polylang still owns *content* translation and locale switching (it sets the
`ar` locale on `/ar/`, which is what makes WordPress pick the `-ar.mo`); gettext owns *UI-chrome* strings.

## Scope (FR)

- **FR-1** Wrap every user-facing hardcoded string in the renderers in the correct `__()`-family call
  with the literal `perego-site` domain — starting with `SiteHeaderRenderer` nav labels + dropdown.
- **FR-2** Load the `perego-site` textdomain on `init` from `perego-site/languages/`; add `Domain Path`.
- **FR-3** Author `perego-site-ar.po` covering every extracted string, compile `perego-site-ar.mo`.
  Reuse the AR wording already proven in `GlobalContent`/`ServiceContent`/`PortfolioContent` for
  consistency; author the rest as standard MSA UI copy.
- **FR-4** No English string may remain in the AR header nav, forms, buttons, or aria-labels.
- **FR-5** No layout/behaviour change; EN routes render identically; all existing gates stay green.

## Non-goals

- Content translation (posts/taxonomies/pages) — Polylang's job, already wired.
- AR journal category terms + AR legal section bodies — tracked owner/content gaps, not UI strings.
- The theme's 3 `perego-theme`-domain strings are in scope only if they surface on public routes.

## Acceptance

AR routes (`/ar/…`) show Arabic nav + form labels + buttons; EN unchanged; Pest/Jest/route-health/
a11y/interactions all green; a new check asserts the AR header nav renders no Latin nav label.
