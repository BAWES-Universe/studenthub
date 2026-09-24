import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const repoRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const tool = path.join(repoRoot, 'tools', 'audit-n8n-s3-workflows.mjs');
const fixture = path.join(repoRoot, 'tools', 'fixtures', 'n8n-s3-workflows.sample.json');

function run(args) {
  return execFileSync(process.execPath, [tool, '--workflows', fixture, ...args], {
    cwd: repoRoot,
    encoding: 'utf8',
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

const markdown = run(['--format', 'markdown']);
assert.match(markdown, /StudentHub n8n S3 lifecycle maintenance/);
assert.match(markdown, /PutBucketLifecycleConfiguration/);
assert.match(markdown, /studenthub-uploads/);
assert.match(markdown, /n8n-s3-access/);
assert.match(markdown, /access-key suffix: DOBNJ/);
assert.doesNotMatch(markdown, /SYNTHETIC-DOBNJ-KEY/);
assert.doesNotMatch(markdown, /synthetic-secret-value-do-not-use/);
assert.doesNotMatch(markdown, /SLACK_SYNTHETIC_TOKEN_DO_NOT_PRINT/);
assert.doesNotMatch(markdown, /Notify Slack/);

const csv = run(['--format', 'csv']);
assert.match(csv, /workflow_id,workflow_name,node_name,node_type,risk_level,signals,credential_refs/);
assert.match(csv, /wf-risky-s3/);
assert.doesNotMatch(csv, /wf-safe-slack/);

console.log('n8n S3 workflow audit checks passed');
