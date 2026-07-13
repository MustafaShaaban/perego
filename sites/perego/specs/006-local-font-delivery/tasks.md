# Tasks: Local Font Delivery

- [x] T001 Acquire the open-licensed Open Sans and Cairo WOFF2 files needed by the locked handoff into `sites/perego/perego-theme/assets/fonts/`.
- [x] T002 Add local `@font-face` declarations in `sites/perego/perego-theme/assets/src/scss/main.scss` for the required weights.
- [x] T003 Remove Google Fonts stylesheet and preconnect output from `sites/perego/perego-theme/functions.php`.
- [x] T004 Build the Perego theme assets and verify the generated CSS references local font files.
- [x] T005 Run Pest, Jest, route-health, a11y, and interaction verification; record the local-font evidence in `sites/perego/docs/visual-acceptance.md`.
- [x] T006 Run applicable guards; update `PROGRESS.md` and `DECISIONS.md`; commit, push, and open a Perego-only PR. Commit `0db8cab`; PR #13 targets the Perego trunk line and is clean with no required checks.
