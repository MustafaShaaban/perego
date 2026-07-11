/**
 * Jest config for perego-site's Interactivity API view scripts (spec 001, T013/T023/T031).
 *
 * Extends the framework's @wordpress/scripts jest-unit preset. `@wordpress/interactivity` is a
 * WordPress runtime script handle, not an installed npm package here, so it's mapped to a local
 * test double (tests-js/wp-interactivity-mock.js) that makes store()/getContext()/getElement()
 * exercisable under jsdom.
 */
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...defaultConfig,
	rootDir: __dirname,
	moduleNameMapper: {
		'^@wordpress/interactivity$':
			'<rootDir>/tests-js/wp-interactivity-mock.js',
	},
	testPathIgnorePatterns: [ '/node_modules/' ],
};
