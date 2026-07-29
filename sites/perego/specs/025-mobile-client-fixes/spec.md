# Spec 025 — The process section on iOS, and the toast on a phone

**Branch:** `fix/025-mobile-client-fixes` (off `chore/corex-v0.40.0-update`)
**Mode:** Client Site Mode
**Status:** Complete (2026-07-29).
**Depends on:** 024 (the duplicate-toast fix lands there — see "Not in this spec").
**Client request:** 2026-07-29, two screenshots from an iPhone 13/14 Pro Max, plus a third report
of the toast appearing twice.

## Goal

Two client-reported defects, both visible only on a phone, both in the theme's presentation layer:

1. **"Our Process" renders wrong on iPhone and right on Samsung.** Every step shows as
   icon | label | description in a row, the description running off the right edge, instead of a
   centred vertical card.
2. **The toast is wrong on mobile.** After a rejected submit it floats across the middle of the
   page, nearly full width, glass over glass, and unreadable. The client asked either for a simpler
   mobile design or for the position/behaviour to be fixed.

## 1. The process step becomes a grid (`perego-wordpress-adapter.scss`)

### What was wrong

The seeded step is a core group block, so WordPress renders it with `is-layout-flex` and applies its
own global styles to it (read off the live page, both rules):

```css
body .is-layout-flex { display: flex; }
.is-layout-flex { flex-wrap: wrap; align-items: center; }
```

The theme's `.process .process-step` set `flex-direction: column` and `flex: 1 1 0` but never reset
`flex-wrap`. A **column** flex container that wraps lays its children out in *several columns* as
soon as its height is definite — icon in one, label in the next, description in the third. That is
precisely the client's screenshot, and it is reproducible from the live CSS: set a definite height on
the step (measured in Chromium against the live page at 428px) and the three children snap onto one
line — FIGURE x34 · P x110 · P x255, identical y. Adding `flex-wrap: nowrap` in the same session put
them back into a column.

### Why the fix is not `flex-wrap: nowrap`

`nowrap` closes that mechanism, and it was the first patch written. It did not ship alone, because
**the client's rendering does not reproduce on any WebKit available here** — Playwright WebKit 26.5,
widths 320 → 900, against the live unfixed CSS, renders every step correctly stacked. The mechanism
is demonstrable; the trigger on their device is not. Repairing one path into a layout the component
should never have been able to reach would have been a guess wearing a fix's clothes.

### What shipped

A step is a vertical stack — icon, label, description — at **every** width; the row on desktop is
the *list's* doing, not the step's. So the step is now a single-column grid:

```scss
.process .process-step {
	display: grid;
	grid-template-columns: minmax(0, 1fr);
	justify-items: center;
	/* …gap, flex item sizing, max-inline-size, text-align unchanged… */
}
```

A grid has no flex direction to lose and no `flex-wrap` to inherit; `display: grid` (two classes)
also outranks WordPress's `body .is-layout-flex { display: flex }` (one class, one element). The
photographed layout cannot be expressed by this rule at all, whatever the trigger turns out to be.
Rendering is unchanged everywhere it was already correct.

The `≤620px` band additionally resets the step to `flex: 0 0 auto`: once the list stacks, growing a
step along the list's (now vertical) main axis only stretches its height, and a stretched height is
what let the old flex box wrap in the first place.

`perego-reference.scss` is untouched — it is the immutable handoff stylesheet, and neutralising
WordPress interference is exactly what the adapter is for.

## 2. The toast becomes a banner on phones (`perego-wordpress-adapter.scss`, `toast.js`)

### What was wrong

Three things at once, all only on a phone:

- `.perego-toasts` is `position: fixed` against the **layout** viewport. A rejected submit focuses
  the first invalid field, iOS opens the keyboard, and the bottom-pinned stack is left floating over
  the middle of the page — over the next section's heading, in the client's screenshot.
- At 428px it was a 380px-wide card with a 29px radius and a 40px medallion: desktop furniture on a
  phone.
- The 55% glass fill over the site's lighter violet bands left the message under 4.5:1.

### What shipped

Below 620px the toast is a banner at the top of the visible area:

| | before | after |
|---|---|---|
| placement | bottom right, layout viewport | top, tracking the **visual** viewport |
| fill | `rgb(22 4 53 / 55%)` + 10px backdrop blur | solid `--panel-overlay`, no blur |
| radius / padding | 29px / 16-18px | 16px / 12-14px |
| medallion | 40px disc, 20px glyph | 26px disc, 15px glyph |
| at once | stacks | one — the newest replaces the previous |
| enters from | below | above, the edge it is pinned to |

`toast.js` writes `--perego-toast-vv-offset` from `window.visualViewport.offsetTop` on its `resize`
and `scroll` events, and **only the phone stylesheet consumes it**, so desktop behaviour is
byte-identical and a browser without the API falls back to `0px`. The accent hairline, the
state-tinted glow, the drain bar, the 44px close target, the `aria-hidden` stack and the form's own
`corex-form__status` live region are all unchanged: nothing about the accessible channel moved.

Top, not bottom, because the bottom of a phone belongs to the keyboard and the submit button — it is
where the failure was photographed — and because a banner at the top is what both mobile platforms
use for a transient notice.

### One outcome, one toast

`show()` now ignores a repeat of the same kind + message inside a second. The framework emits
`corex:form:error` from two branches and `join-form/view.js` emits its own; two identical cards read
as two failures.

## Not in this spec

The **duplicate toast** the client reported on "Start a project" is a regression of the open v0.40.0
update, not of this work: v0.40.0 adopted our #148 patch upstream, so `corex-runtime.js` emits
`corex:form:error` itself while the theme still ran the shim written when it did not. Fixed on
`chore/corex-v0.40.0-update` (PR #44), measured on the live form: one click, two events, two toasts.
The repeat guard above is a second line of defence, not the fix.

## Cache

`main.css` is enqueued at the theme's own version and `Version: 0.1.0` had not moved since launch,
so a returning phone would have kept the old stylesheet and shown the old bug. Theme version → 0.1.1
(`style.css`, `package.json`). The JS handle already busts on a content hash.

## Verification

- `perego-site`: **47 suites / 312 tests** green, including 9 new for `toast.js`
  (`tests-js/theme-toast.test.js`).
- Playwright **WebKit 26.5** and **Chromium**, EN and `/ar/` (RTL), 375 → 1440: every step
  `display: grid`, children stacked on a shared centre line, nothing overflowing the viewport; the
  list still a column ≤620 and a row above it.
- WebKit at 428×926, two rejected submits in a row: **one** banner, `top: 8px`, 412px wide, 16px
  radius, solid fill, `--perego-toast-vv-offset` written.
- Screenshots (not committed): the process band at 428 and 1440, and the banner in place.

**Still open, and it is the whole point of the report:** neither defect's *device* condition can be
reproduced here. The client's own iPhone is the only conclusive check for the process section and
for the keyboard case. Ask for a re-test on the deployed site before closing.
