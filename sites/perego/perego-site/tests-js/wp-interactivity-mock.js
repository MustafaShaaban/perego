/**
 * Test double for `@wordpress/interactivity` (spec 001, T013/T023/T031). WordPress provides this
 * module as a runtime script handle, not an installed npm package, so view.js's store()/
 * getContext()/getElement() calls need a stand-in to run under Jest. store() records the config
 * passed to it under its namespace so a test can pull the real actions/callbacks back out;
 * getContext()/getElement() return whatever the test last set via the __set* helpers below.
 */
const registry = {};
let mockContext = {};
let mockElement = { ref: null };

function store( namespace, config ) {
	registry[ namespace ] = config;

	return config;
}

function getContext() {
	return mockContext;
}

function getElement() {
	return mockElement;
}

function __getStore( namespace ) {
	return registry[ namespace ];
}

function __setMockContext( context ) {
	mockContext = context;
}

function __setMockElement( element ) {
	mockElement = element;
}

module.exports = {
	store,
	getContext,
	getElement,
	__getStore,
	__setMockContext,
	__setMockElement,
};
