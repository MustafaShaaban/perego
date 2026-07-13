# Implementation Plan: Local Font Delivery

**Branch**: `feature/006-local-font-delivery` | **Date**: 2026-07-13 | **Spec**: [spec.md](./spec.md)

## Summary

Bundle the exact handoff font families as local WOFF2 assets in the disposable Perego theme. Register token-compatible `@font-face` declarations, remove external font enqueues/preconnects, and validate in the browser with external access denied.

## Constitution Check

- [x] Theme-only presentation and asset work; no CoreX behavior changes.
- [x] Font-family names remain theme token values; no replacement design system.
- [x] Assets are local and only used by the theme stylesheet.
- [x] Arabic remains a first-class RTL verification target.
- [x] This implementation traces to Spec 006 and requires docs, guards, and verification.

## Files

```text
sites/perego/perego-theme/
├── assets/fonts/                 # Local WOFF2 font sources
├── assets/src/scss/main.scss     # @font-face declarations
└── functions.php                 # Remove remote font enqueue/preconnects
```
