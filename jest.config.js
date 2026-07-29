/**
 * Jest config for Corex JS unit tests (block editor scripts + the shared form validator).
 *
 * Extends @wordpress/scripts' default unit config (JSX transform, jsdom, the wp babel
 * preset) and excludes the bundled WordPress install under `wp/` so the run covers only
 * Corex source — not the WooCommerce/core tests that ship inside the WP checkout.
 *
 * `dist/` and `build/` are excluded for the same reason the linters exclude them: they are
 * generated copies of the source, so including them ran 17 suites twice against whatever
 * the last `npm run build:dist` happened to leave behind. That also made the local suite
 * count (62) disagree with CI's (45), because `dist/` is git-ignored and never exists there
 * — a number that changes with your working directory is not a number worth reporting.
 */
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...defaultConfig,
	transform: {
		...defaultConfig.transform,
		'\\.mjs$': require.resolve(
			'@wordpress/scripts/config/babel-transform'
		),
	},
	testPathIgnorePatterns: [
		'/node_modules/',
		'/build/',
		'<rootDir>/wp/',
		'<rootDir>/dist/',
		'<rootDir>/docs-app/',
		// Client sites under `sites/` are a fork-level addition upstream does not have, and each
		// carries its own Jest config — Perego's maps `@wordpress/interactivity` to a local mock,
		// because that module is a WordPress runtime script handle, not an installed package. Swept
		// in from here the mapping is absent and every Interactivity view test fails to resolve it.
		// Those suites run from their own directory — `sites/perego/perego-site` is not an npm
		// workspace of this root, so `--workspace=` does not reach it:
		// `cd sites/perego/perego-site && npm run test:js`.
		'<rootDir>/sites/',
		// Agent worktrees are full checkouts of this repo, so every suite inside one is a duplicate of
		// a suite already being run — and a duplicate that resolves its imports against the worktree's
		// own (usually absent) node_modules. Left in, they contributed 8 failing suites / 76 failing
		// tests to this root run, none of them real: `Cannot find module '@wordpress/interactivity'`
		// from a copy of Perego's blocks that the `<rootDir>/sites/` pattern above cannot match,
		// because it is anchored at the root and these live under `.claude/worktrees/<name>/sites/`.
		// The directory is git-excluded, so this only ever affects local runs — which is precisely
		// when a phantom failure is most likely to be mistaken for a real one.
		'<rootDir>/.claude/',
	],
};
