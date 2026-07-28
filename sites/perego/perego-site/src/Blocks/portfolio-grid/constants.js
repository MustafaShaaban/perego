/**
 * Mirror of `PortfolioGridRenderer::PER_PAGE`.
 *
 * The canvas needs it to draw the same pager the front end draws — the pager is part of the structure
 * the parity harness compares, even though it is inert in the editor. `view.js` reads the real value
 * off `data-per-page` rather than importing this, so the front end has one source of truth and this
 * only has to agree with it; `parity.test.js` fails if it stops agreeing.
 */
export const PER_PAGE = 9;
