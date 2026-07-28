# Research decisions

| Decision | Evidence and choice |
|---|---|
| Editor rendering | Existing Header/Footer/Hero blocks expose controls but render editor notes/fields rather than the component DOM. Reuse renderer-normalized contracts and approved classes in editor-only previews. |
| Shared foundation | Existing blocks duplicate JSON-string repeaters and media controls. Introduce a small plugin-owned foundation, not ACF or a global UI library. |
| Taxonomies | `ClientPostType` and `ProjectPostType` currently use `hierarchical: false`; changing registration preserves existing term IDs and assignments. Migration verifies localized term relations. |
| Template identity | Slugs are translated and mutable; service templates use an explicit canonical key/meta with a stable UI label. |
| Visual freeze | Public CSS/render behavior remains untouched unless sharing a class/data normalizer produces identical output; editor styles are scoped. |
| Baseline | Existing Spec 020 screenshots are an input, not sufficient coverage for all routes/states. This spec records a complete EN/AR viewport and interaction matrix before each slice. |
