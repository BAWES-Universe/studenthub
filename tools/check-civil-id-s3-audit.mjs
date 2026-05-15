#!/usr/bin/env node

import { execFileSync } from 'node:child_process';
import { mkdtempSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { tmpdir } from 'node:os';
import assert from 'node:assert/strict';

const root = new URL('..', import.meta.url).pathname;
const script = join(root, 'tools/audit-civil-id-s3-objects.mjs');
const dir = mkdtempSync(join(tmpdir(), 'civil-id-s3-audit-'));
const candidates = join(dir, 'candidates.csv');
const permanent = join(dir, 'permanent.txt');
const temp = join(dir, 'temp.txt');

writeFileSync(candidates, [
  'candidate_id,side,filename,candidate_updated_at',
  '100,front,front-present.jpg,2026-05-01',
  '101,back,back-legacy.jpg,2026-05-02',
  '102,front,front-temp.jpg,2026-05-03',
  '103,back,missing.jpg,2026-05-04',
  '104,front,photos/already-prefixed.jpg,2026-05-05',
].join('\n'));

writeFileSync(permanent, [
  'photos/front-present.jpg',
  'candidate-civil-id/back-legacy.jpg',
  'photos/already-prefixed.jpg',
].join('\n'));

writeFileSync(temp, [
  'photos/front-temp.jpg',
].join('\n'));

const markdown = execFileSync(process.execPath, [
  script,
  '--candidates',
  candidates,
  '--permanent-objects',
  permanent,
  '--temp-objects',
  temp,
  '--emit-copy-commands',
], { encoding: 'utf8' });

assert.match(markdown, /Present in permanent photos prefix: 2/);
assert.match(markdown, /Recoverable from legacy permanent prefix: 1/);
assert.match(markdown, /Recoverable from temp bucket export: 1/);
assert.match(markdown, /Missing from all supplied exports: 1/);
assert.match(markdown, /aws s3 cp 's3:\/\/studenthub-uploads\/candidate-civil-id\/back-legacy.jpg' 's3:\/\/studenthub-uploads\/photos\/back-legacy.jpg'/);
assert.match(markdown, /aws s3 cp 's3:\/\/studenthub-public-anyone-can-upload-24hr-expiry\/photos\/front-temp.jpg' 's3:\/\/studenthub-uploads\/photos\/front-temp.jpg'/);

const csv = execFileSync(process.execPath, [
  script,
  '--candidates',
  candidates,
  '--permanent-objects',
  permanent,
  '--temp-objects',
  temp,
  '--format',
  'csv',
], { encoding: 'utf8' });

assert.match(csv, /101,back,back-legacy.jpg,recover_from_legacy/);
assert.match(csv, /102,front,front-temp.jpg,recover_from_temp/);
assert.match(csv, /103,back,missing.jpg,missing/);

console.log('Civil ID S3 audit helper check passed.');
