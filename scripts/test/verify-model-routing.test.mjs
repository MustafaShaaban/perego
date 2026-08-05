import assert from 'node:assert/strict';
import { cp, mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';
import {
	parseClaudeAgent,
	validateRepository,
} from '../verify-model-routing.mjs';

const repositoryRoot = resolve(
	dirname( fileURLToPath( import.meta.url ) ),
	'..',
	'..'
);
const routingFiles = [
	'.codex/config.toml',
	'.codex/MODEL-ROUTING.md',
	'.codex/agents/perego_scout.toml',
	'.codex/agents/perego_routine_worker.toml',
	'.codex/agents/perego_deep_worker.toml',
	'.codex/agents/perego_critical_reviewer.toml',
	'.claude/settings.json',
	'.claude/MODEL-ROUTING.md',
	'.claude/agents/perego-explorer.md',
	'.claude/agents/perego-routine-worker.md',
	'.claude/agents/perego-deep-worker.md',
	'.claude/agents/perego-critical-reviewer.md',
	'docs/en/04-team-workflow/model-routing.md',
	'docs/ar/04-team-workflow/model-routing.md',
	'AGENTS.md',
	'CLAUDE.md',
];

async function copyRoutingFiles( root ) {
	for ( const file of routingFiles ) {
		const target = join( root, file );
		await mkdir( dirname( target ), { recursive: true } );
		await cp( join( repositoryRoot, file ), target );
	}
}

async function routingFixture() {
	const root = await mkdtemp( join( tmpdir(), 'perego-model-routing-' ) );
	await copyRoutingFiles( root );
	return root;
}

test( 'the committed routing assets pass static validation', () => {
	assert.deepEqual( validateRepository( repositoryRoot ), [] );
} );

test( 'the validator identifies a wrong Codex route model', async ( t ) => {
	const root = await routingFixture();
	t.after( () => rm( root, { recursive: true, force: true } ) );
	const path = join( root, '.codex', 'agents', 'perego_scout.toml' );
	const text = await readFile( path, 'utf8' );
	await writeFile( path, text.replace( 'gpt-5.6-luna', 'gpt-5.6-sol' ) );
	assert.match(
		validateRepository( root ).join( '\n' ),
		/perego_scout\.toml: expected model = "gpt-5\.6-luna"/
	);
} );

test( 'the validator rejects write-capable critical reviewers', async ( t ) => {
	const root = await routingFixture();
	t.after( () => rm( root, { recursive: true, force: true } ) );
	const path = join(
		root,
		'.claude',
		'agents',
		'perego-critical-reviewer.md'
	);
	const text = await readFile( path, 'utf8' );
	const parsed = parseClaudeAgent( text, path );
	await writeFile(
		path,
		text.replace( parsed.fields.get( 'tools' ), 'Read, Grep, Glob, Write' )
	);
	assert.match(
		validateRepository( root ).join( '\n' ),
		/perego-critical-reviewer\.md: read-only agent exposes a mutating or nested tool/
	);
} );
