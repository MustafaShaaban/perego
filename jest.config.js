/**
 * Jest config for Corex JS unit tests (block editor scripts + the shared form validator).
 *
 * Extends @wordpress/scripts' default unit config (JSX transform, jsdom, the wp babel
 * preset) and excludes the bundled WordPress install under `wp/` so the run covers only
 * Corex source — not the WooCommerce/core tests that ship inside the WP checkout.
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
		'<rootDir>/wp/',
		'<rootDir>/docs-app/',
		// Client sites under `sites/` are a fork-level addition upstream does not have, and each
		// carries its own Jest config — Perego's maps `@wordpress/interactivity` to a local mock,
		// because that module is a WordPress runtime script handle, not an installed package. Swept
		// in from here the mapping is absent and every Interactivity view test fails to resolve it.
		// Those suites run from their own directory — `sites/perego/perego-site` is not an npm
		// workspace of this root, so `--workspace=` does not reach it:
		// `cd sites/perego/perego-site && npm run test:js`.
		'<rootDir>/sites/',
	],
};
