# Data Model: Lighthouse Client Performance

This feature introduces no persistent database entities or migrations. Its
contracts are build-time and rendered-markup records.

## SourceImage

| Field | Type | Rules |
|---|---|---|
| `path` | repository-relative path | Must be under the Perego theme source-image directory |
| `format` | `png`, `jpg`, or `jpeg` | Must be decodable by the image build |
| `width` | positive integer | Read from source metadata |
| `height` | positive integer | Read from source metadata |
| `bytes` | positive integer | Used as the fallback-size safety baseline |

## OptimizedVariant

| Field | Type | Rules |
|---|---|---|
| `source` | SourceImage reference | Required |
| `format` | original format or `webp` | One fallback plus one WebP sibling |
| `path` | repository-relative path | Deterministic stem and extension |
| `width` | positive integer | Must preserve intended intrinsic dimensions |
| `height` | positive integer | Must preserve intended intrinsic dimensions |
| `bytes` | positive integer | Fallback must not exceed canonical source bytes |

## MediaRenderContract

| Field | Type | Rules |
|---|---|---|
| `modernSrc` | URL | WebP sibling for supported browsers |
| `fallbackSrc` | URL | Original-format valid fallback |
| `width` | positive integer | Accurate intrinsic width |
| `height` | positive integer | Accurate intrinsic height |
| `alt` | string | Meaningful text or empty for decorative imagery |
| `loading` | `eager` or `lazy` | Chosen by viewport importance |
| `decoding` | `async` | Non-blocking decode unless evidence requires otherwise |
| `class` | token list | Existing presentation hook preserved |

### Validation Rules

1. Both URLs must resolve successfully.
2. Width and height must match the fallback's intrinsic aspect ratio.
3. WebP must be selected by a supporting browser.
4. The fallback must remain visible if the WebP source is unavailable.
5. Existing CSS selectors and JavaScript hooks must remain intact.

## AuditFinding

| Field | Type | Rules |
|---|---|---|
| `auditId` | string | Lighthouse audit identifier |
| `origin` | URL/origin | Evidence source |
| `owner` | enum | `perego`, `corex`, `wordpress`, `third-party`, `extension` |
| `actionable` | boolean | True only when owner is Perego and the issue reproduces |
| `resolution` | enum | `fixed`, `documented`, `out-of-scope`, `unreproduced` |
| `evidence` | string | Test, response, or clean-audit reference |

### State Transition

`reported` → `classified` → (`actionable` → `tested` → `fixed` → `verified`)  
or  
`reported` → `classified` → `documented-out-of-scope`
