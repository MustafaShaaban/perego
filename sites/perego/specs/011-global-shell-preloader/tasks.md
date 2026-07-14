# Spec 011 — Tasks

- [x] T001 **Preloader rebuilt to the locked handoff.** The renderer emitted only a bare
    `.perego-preloader` "Loading…" placeholder; the authoritative reference stylesheet already carried the
    full `.preloader*` design (stage, 3 rings, glow, logo, bar, wordmark, keyframes, `is-hidden`). Rewrote
    `PreloaderRenderer` to emit the exact handoff structure (`class="preloader"` + rings/glow/logo/bar/
    wordmark `بيريجو · PEREGO`, logo from the theme URI), kept the Interactivity wiring
    (`data-wp-class--is-hidden` → session/soft-0.9s/hard-2.5s/reduced-motion logic in view.js, unchanged).
    Removed the orphaned `preloader/style.scss` + its `style` key/import (one visual authority = reference).
    Verified live: homepage renders all handoff parts; `main.css` (enqueued) styles them. Pest 243/243,
    Jest preloader 6/6. Updated the render test to the handoff structure.
- [x] T002 **Header/desktop-nav/services-dropdown fidelity — matched to the locked handoff.** The nav
    diverged from the handoff on three points: About Us pointed at a hard-coded `/about` route (live 404),
    the top-level Services pointed at `/services` instead of the homepage `#services` teaser anchor, and the
    dropdown used long service names ("Video Editing & Post-Production") where the handoff uses short labels
    ("Video Editing"). Fixed `SiteHeaderRenderer::navItems()`: About Us → `/#about`, Services → `/#services`,
    dropdown → the four short handoff labels (hrefs unchanged: `/services/{slug}`). Generalized `isActive()`
    to treat any `#`-anchor href as a non-route (never painting active/aria-current). Verified live: all
    hrefs/labels match the handoff and every anchor target id (`#about #services #clients #hero #contact`)
    exists on the homepage. Header tests 13/13.
- [x] T003 **Sticky/scrolled header state — exact.** The reference stylesheet's `.site-header` /
    `.site-header.is-scrolled` rules (`position:sticky; top:0; z-index:100`, blur/box-shadow on scroll) are
    byte-identical to the handoff `css/styles.css`; the `is-scrolled` toggle is wired in the block's view.js
    (verified in spec 008's `verify-interactions.mjs`). No change needed.
- [x] T004 Mobile menu built + interaction-verified in spec 008 (`verify-interactions.mjs`): slide-in panel
    (`#mainNav`), backdrop (`#navBackdrop`), hamburger (`#navToggle`), keydown trap
    (`actions.handleMenuKeydown`), tap-accordion (`actions.toggleMobileDropdown`). Markup asserted in header
    tests. No fidelity change needed.
- [x] T005 **Language switcher — real nav, current marker, RTL flip — verified.** Live AR home
    (`/ar/الرئيسية/`) renders `dir="rtl" lang="ar"`, Arabic nav labels (من نحن / خدماتنا / أعمالنا …), the
    AR toggle button carries `is-active aria-current`, and the EN→AR switch is a real anchor to the
    translated Polylang URL. See T010 follow-up for the separate nav-link localization gap.
- [x] T006 **Standard footer — fidelity confirmed.** Live homepage footer renders the handoff's three
    columns in order: `footer-contact` (logo, Contact us, channels, blurb, social) + `footer-quick-message`
    (live CoreX form) + `footer-careers` (Join-us block + join form) + legal bottom bar. Matches the handoff
    `index.html` composition. Footer tests pass.
- [x] T007 **Contact flat footer — corrected.** The flat variant was inverted: it kept the quick-message
    column and dropped careers, but the handoff `contact.html` keeps contact + the "Join us"/careers column
    and drops quick-message. Fixed `SiteFooterRenderer::render()` to render quick-message only when
    `! $flat`, and always render careers. Verified live: `/contact/` footer = `footer-contact` +
    `footer-careers` (no quick-message); homepage still shows all three. Footer test updated.
- [x] T008 **Hard-coded `/about` removed.** About Us now targets the `#about` homepage anchor (T002);
    nothing links to the 404 `/about` route. Social URLs remain the handoff's documented placeholders
    (`SiteFooterRenderer::SOCIAL_LINKS`, commented as pending real owner handles) — not fabricated accounts.
- [x] T009 **FSE editability — header/footer are first-class template parts.** Declared `templateParts` in
    `perego-theme/theme.json` (header→area `header`; footer, footer-flat→area `footer`), so the Site Editor
    lists them by title in the correct areas. Verified: `get_block_templates(…, 'wp_template_part')` returns
    all three with the right `area`. Parts embed the server-rendered blocks (`edit()` shows a labelled
    preview; `save:null`).
- [x] T010 **EN/AR visual acceptance — captured against the live site.** Playwright captures (saved to the
    gitignored `output/playwright/`): `011-en-desktop-header.png` — EN header matches the handoff (logo
    "Perego بيريجو", Home active/underlined, About Us, Services ▾, Work, Journal, Clients, Contact Us, accent
    "Start a Project", AR/EN toggle with EN active); `011-en-fullpage-footer-3col.png` — homepage footer is
    the handoff's three columns (Contact us + channels + social / quick-message Full name·E-mail·message·Send
    / Join us CV form) + legal bar; `011-ar-desktop-header-rtl.png` — full RTL mirror (logo right, nav
    right-aligned Arabic الرئيسية/من نحن/خدماتنا ▾/أعمالنا/المدونة/عملاؤنا/تواصل معنا, CTA + toggle left, hero
    "بماذا نؤمن" right-aligned); `011-contact-flat-footer-2col.png` — `/contact` flat footer is exactly two
    columns (Contact us + Join us), **no quick-message column**, confirming the T007 fix. Focus states +
    reduced-motion were verified in spec 008 (`verify-interactions.mjs`, a11y pass); the reference CSS is
    byte-identical to the handoff so the responsive breakpoints carry over. **Tooling note:** the
    playwright-cli `resize` to a mobile width did not take effect this session (viewport stayed 1280), so the
    mobile hamburger was not re-captured here — T004's mobile menu remains covered by spec 008's verified
    interactions and the header tests' markup assertions.
    - **Follow-up (documented, not a T011 blocker):** the primary-nav item hrefs are emitted with
      `home_url()`, so on AR pages every nav link (Home/Work/Journal/services/Contact and the `#about`/
      `#services`/`#clients` anchors) points at the **EN** base URL — an AR visitor clicking any nav item is
      thrown back to English. This is a systemic Polylang nav-localization gap (needs a language-aware base,
      e.g. `pll_home_url()` + translated permalinks for all items), out of scope for the shell-fidelity spec
      and best fixed as a focused i18n-routing task; the language *switcher* itself works. Logged here so it
      is not silently treated as done.
- [ ] T011 Full suite + guards; update durable memory; open PR.
