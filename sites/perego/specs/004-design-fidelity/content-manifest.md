# Perego — Content & Asset Manifest + Launch-Blocker Audit (US3 / T019, T021)

**Purpose**: classify every public-facing content/asset record as **Approved**, **Demo**,
**Placeholder**, or **Legal-review-required**, and enumerate the launch blockers. Design fidelity
(spec 004 Phase 4) is complete; this is the launch-readiness gate. **T020** (replacing Demo/Placeholder
with owner-supplied production content) is blocked on owner material and is not done here.

Classification key:
- **Approved** — owner-approved final content/asset, launch-ready.
- **Demo** — deliberate example content standing in for real work, safe to show with its demo marker,
  must be replaced before launch.
- **Placeholder** — generic/WP-default filler that must not ship.
- **Legal-review-required** — must be reviewed by counsel before publication.

## Assets (media)

| Asset / group | Where | Class | Note |
| --- | --- | --- | --- |
| Logo (`logo-full.png`), hero/about/service-card background images | header, home, services | **Approved** | From the locked handoff set |
| Service "what we do" media (`ui-video-editing.png`, `ui-motion-graphics.png`, `ui-graphic-design.png`) | service singles | **Approved** | Handoff UI stills; website-making reuses `ui-graphic-design.png` per handoff |
| Project featured images (`portfolio-1..9.png`) | work archive + project singles | **Demo** | Handoff stills seeded as real featured images (`seed-project-media.php`); replace with Perego's real project photography |
| Journal featured images (`portfolio-1/3/6.png`) | journal archive + single posts | **Demo** | Same stills reused for post cards (`seed-journal-media.php`) |
| Client tile images/logos | home clients carousel | **Placeholder** | Sample client tiles — see clients row below |

## Content records

| Record / group | Where | Class | Note / blocker |
| --- | --- | --- | --- |
| Home hero, About Us / mission (front-page `post_content`) | `/` | **Approved** | Handoff copy, editor-canvas managed (T012) |
| Clients carousel names ("Sample Corporate/Individual Client") | home `#clients` | **Placeholder** | FR-006 launch blocker — needs real client names/logos/consent |
| Services archive + 4 service singles (whatwedo/process in `post_content`) | `/services/*` | **Approved** | Handoff copy, editor-canvas managed (T014) |
| Project case studies (`post_content`) + titles/meta | `/work/*` | **Demo** | Example case studies; demo-note shown on the archive. Replace with real projects |
| Work archive demo-note marker | `/work/` | **Demo (intentional marker)** | "Example projects shown below — to be replaced with Perego's real work." |
| Journal posts (6 EN+AR example articles) | `/journal/*` | **Demo** | Example articles; body copy is placeholder ("Editable on the canvas"). Author set to site owner (Mostafa Emam) |
| Terms / Privacy body | `/terms/`, `/privacy/` | **Legal-review-required** | Draft/review notice rendered (`reviewNote`). Must be replaced with counsel-reviewed text before publication |
| `sample-page` body | `/sample-page/` | **Placeholder** | WordPress default "Sample Page" copy (bike-messenger / XYZ Doohickey / dashboard link). Remove or replace |
| Contact brief + footer quick-message + careers/join forms | `/contact/`, footer, careers | **Approved** | Real functional forms (CoreX Forms), validated + styled |
| Global contact details (emails/phones/socials) | footer | **Approved** | Owner contact channels wired via `GlobalContent::footer()` |

## Launch blockers (ranked)

1. ~~**UI-string i18n unwired (bilingual-launch blocker).**~~ **RESOLVED in spec 005** (gettext
   `.po`/`.mo`; constitution-aligned, no Polylang dependency). The `perego-site` textdomain now loads a
   `perego-site-ar.mo`, the header nav labels are `__()`-wrapped, and the framework (`corex`) form
   submit/status strings are localized via a client `gettext_corex` filter. AR routes render Arabic
   nav + form labels + buttons + status; EN unchanged. See `../005-i18n-strings/`.
2. **Clients carousel placeholder names (FR-006).** Public home section shows fabricated client names.
3. **Legal copy is draft** (Terms/Privacy) — needs counsel review; AR legal section bodies are unseeded.
4. **Demo project/journal content** — example case studies + articles + demo photography must be
   replaced with real, owner-approved work.
5. **`sample-page` WP-default copy** — remove or replace; no AR translation exists.
6. **AR journal category terms render in English** — WordPress `category` taxonomy terms need AR
   translation (Polylang term translation, an admin/content task). Detail:
   [evidence/journal.md](./evidence/journal.md).

## Not blockers (verified safe to ship)

- All 16 route templates are design-complete against the handoff (route-matrix.md).
- Forms are functional, validated, spam-trapped, and styled.
- SEO (100), accessibility (12-page axe: 0 serious/critical), route-health (72/0), interactions (4/4).
- No fabricated ratings/reviews/awards/prices (StructuredData test enforces this).
