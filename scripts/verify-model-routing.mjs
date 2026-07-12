#!/usr/bin/env node

import { readFileSync, readdirSync, statSync } from 'node:fs';
import { resolve, join, relative, isAbsolute } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const CODEX_AGENTS = {
  perego_scout: { model: 'gpt-5.6-luna', effort: 'low', sandbox: 'read-only' },
  perego_routine_worker: { model: 'gpt-5.6-terra', effort: 'medium', sandbox: 'workspace-write' },
  perego_deep_worker: { model: 'gpt-5.6-sol', effort: 'high', sandbox: 'workspace-write' },
  perego_critical_reviewer: { model: 'gpt-5.6-sol', effort: 'high', sandbox: 'read-only' },
};

const CLAUDE_AGENTS = {
  'perego-explorer': { model: 'haiku', effort: 'low', readOnly: true },
  'perego-routine-worker': { model: 'sonnet', effort: 'medium', readOnly: false },
  'perego-deep-worker': { model: 'opus', effort: 'high', readOnly: false },
  'perego-critical-reviewer': { model: 'opus', effort: 'high', readOnly: true },
};

function readText(path, errors) {
  try {
    return readFileSync(path, 'utf8');
  } catch {
    errors.push(`${path}: required file is missing or unreadable`);
    return '';
  }
}

function parseToml(text, file, errors) {
  const values = new Map();
  const multiline = /^(\w+)\s*=\s*"""([\s\S]*?)"""/gm;
  for (const match of text.matchAll(multiline)) values.set(match[1], match[2].trim());
  const lines = text.replace(/^\w+\s*=\s*"""[\s\S]*?"""\s*$/gm, '').split(/\r?\n/);
  for (const [index, raw] of lines.entries()) {
    const line = raw.trim();
    if (!line || line.startsWith('#') || line.startsWith('[') || line.includes('"""')) continue;
    const match = line.match(/^(\w+)\s*=\s*("(?:[^"\\]|\\.)*"|\d+)\s*$/);
    if (!match) {
      errors.push(`${file}:${index + 1}: unsupported or malformed TOML assignment`);
      continue;
    }
    const [, key, rawValue] = match;
    values.set(key, rawValue.startsWith('"') ? JSON.parse(rawValue) : Number(rawValue));
  }
  return values;
}

export function parseClaudeAgent(text, file = '<agent>') {
  const match = text.match(/^---\r?\n([\s\S]*?)\r?\n---\r?\n([\s\S]*)$/);
  if (!match) throw new Error(`${file}: YAML frontmatter must be delimited by ---`);
  const fields = new Map();
  for (const [index, line] of match[1].split(/\r?\n/).entries()) {
    const pair = line.match(/^([A-Za-z][A-Za-z0-9]*):\s*(.+)$/);
    if (!pair) throw new Error(`${file}:${index + 2}: malformed YAML frontmatter field`);
    fields.set(pair[1], pair[2].trim());
  }
  return { fields, body: match[2] };
}

function listFiles(directory, extension) {
  try {
    return readdirSync(directory)
      .filter((name) => name.endsWith(extension))
      .map((name) => join(directory, name));
  } catch {
    return [];
  }
}

function assertValue(values, key, expected, file, errors) {
  if (values.get(key) !== expected) {
    errors.push(`${file}: expected ${key} = ${JSON.stringify(expected)}`);
  }
}

function scanHygiene(root, files, errors) {
  const forbidden = [/gpt-5\.1-codex/i, /(?:api[_-]?key|token|secret)\s*=\s*["'][^"']+/i, /[A-Za-z]:\\Users\\/];
  for (const file of files) {
    const text = readText(file, errors);
    for (const pattern of forbidden) {
      if (pattern.test(text)) errors.push(`${file}: contains forbidden deprecated model, credential-like value, or absolute local path`);
    }
  }
  for (const file of files) {
    const path = relative(root, file);
    if (isAbsolute(path) || path.startsWith('..')) errors.push(`${file}: escapes validation root`);
  }
}

export function validateRepository(rootInput) {
  const root = resolve(rootInput);
  const errors = [];
  const configPath = join(root, '.codex', 'config.toml');
  const config = parseToml(readText(configPath, errors), configPath, errors);
  assertValue(config, 'model', 'gpt-5.6-terra', configPath, errors);
  assertValue(config, 'model_reasoning_effort', 'medium', configPath, errors);
  assertValue(config, 'max_threads', 3, configPath, errors);
  assertValue(config, 'max_depth', 1, configPath, errors);

  const codexDirectory = join(root, '.codex', 'agents');
  const codexFiles = listFiles(codexDirectory, '.toml');
  const codexNames = new Set();
  for (const file of codexFiles) {
    const values = parseToml(readText(file, errors), file, errors);
    for (const key of ['name', 'description', 'developer_instructions', 'model', 'model_reasoning_effort', 'sandbox_mode']) {
      if (!values.get(key)) errors.push(`${file}: missing required ${key}`);
    }
    const name = values.get('name');
    if (codexNames.has(name)) errors.push(`${file}: duplicate Codex agent name ${name}`);
    codexNames.add(name);
    const expected = CODEX_AGENTS[name];
    if (expected) {
      assertValue(values, 'model', expected.model, file, errors);
      assertValue(values, 'model_reasoning_effort', expected.effort, file, errors);
      assertValue(values, 'sandbox_mode', expected.sandbox, file, errors);
    }
  }
  for (const name of Object.keys(CODEX_AGENTS)) {
    if (!codexNames.has(name)) errors.push(`${codexDirectory}: missing required Codex agent ${name}`);
  }

  const settingsPath = join(root, '.claude', 'settings.json');
  let settings = {};
  try {
    settings = JSON.parse(readText(settingsPath, errors));
  } catch {
    errors.push(`${settingsPath}: invalid JSON`);
  }
  if (settings.model !== 'sonnet') errors.push(`${settingsPath}: expected model "sonnet"`);
  if (settings.effortLevel !== 'medium') errors.push(`${settingsPath}: expected effortLevel "medium"`);

  const claudeDirectory = join(root, '.claude', 'agents');
  const claudeFiles = listFiles(claudeDirectory, '.md');
  const claudeNames = new Set();
  for (const file of claudeFiles) {
    let agent;
    try {
      agent = parseClaudeAgent(readText(file, errors), file);
    } catch (error) {
      errors.push(error.message);
      continue;
    }
    const { fields } = agent;
    for (const key of ['name', 'description', 'tools', 'model', 'effort', 'permissionMode', 'maxTurns']) {
      if (!fields.get(key)) errors.push(`${file}: missing required ${key}`);
    }
    const name = fields.get('name');
    if (claudeNames.has(name)) errors.push(`${file}: duplicate Claude agent name ${name}`);
    claudeNames.add(name);
    const expected = CLAUDE_AGENTS[name];
    if (expected) {
      if (fields.get('model') !== expected.model) errors.push(`${file}: expected model ${expected.model}`);
      if (fields.get('effort') !== expected.effort) errors.push(`${file}: expected effort ${expected.effort}`);
      if (fields.get('tools').split(',').map((value) => value.trim()).includes('Agent')) errors.push(`${file}: nested Agent capability is prohibited`);
      const tools = fields.get('tools') ?? '';
      if (expected.readOnly && /\b(?:Edit|Write|Bash|Agent)\b/.test(tools)) errors.push(`${file}: read-only agent exposes a mutating or nested tool`);
      if (expected.readOnly && fields.get('permissionMode') !== 'plan') errors.push(`${file}: read-only agent must use permissionMode plan`);
    }
    if (/^isolation:\s*worktree/m.test(readText(file, errors))) errors.push(`${file}: Claude worktree isolation is prohibited`);
  }
  for (const name of Object.keys(CLAUDE_AGENTS)) {
    if (!claudeNames.has(name)) errors.push(`${claudeDirectory}: missing required Claude agent ${name}`);
  }

  const sharedPolicy = join(root, 'docs', 'en', '04-team-workflow', 'model-routing.md');
  const arabicPolicy = join(root, 'docs', 'ar', '04-team-workflow', 'model-routing.md');
  const rootGuidance = [join(root, 'AGENTS.md'), join(root, 'CLAUDE.md')];
  const requiredDocuments = [sharedPolicy, arabicPolicy, ...rootGuidance, join(root, '.codex', 'MODEL-ROUTING.md'), join(root, '.claude', 'MODEL-ROUTING.md')];
  for (const file of requiredDocuments) readText(file, errors);
  const policyText = readText(sharedPolicy, errors);
  for (const token of ['L — low-cost exploration', 'M — routine work', 'H — complex/high-risk', 'V — critical verification', 'Single-writer', 'Cross-provider handoff', 'CLAUDE_CODE_SUBAGENT_MODEL', 'CLAUDE_CODE_EFFORT_LEVEL', 'rollback']) {
    if (!policyText.includes(token)) errors.push(`${sharedPolicy}: missing required routing policy section or safeguard: ${token}`);
  }
  for (const file of rootGuidance) {
    if (!readText(file, errors).includes('Model Routing Gate')) errors.push(`${file}: must reference Model Routing Gate`);
  }

  scanHygiene(root, [...requiredDocuments, configPath, ...codexFiles, settingsPath, ...claudeFiles], errors);
  return errors;
}

function main() {
  const rootIndex = process.argv.indexOf('--root');
  const root = rootIndex >= 0 ? process.argv[rootIndex + 1] : resolve(fileURLToPath(new URL('..', import.meta.url)));
  if (!root) throw new Error('--root requires a directory');
  const errors = validateRepository(root);
  if (errors.length > 0) {
    console.error(`Model-routing validation failed with ${errors.length} issue(s):`);
    for (const error of errors) console.error(`- ${error}`);
    process.exitCode = 1;
    return;
  }
  console.log('Model-routing validation passed.');
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) main();
