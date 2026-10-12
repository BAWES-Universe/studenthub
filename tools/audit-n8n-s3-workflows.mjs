import fs from 'node:fs';
import path from 'node:path';

const BUCKET_ADMIN_EVENTS = [
  'PutBucketLifecycleConfiguration',
  'DeleteBucketCors',
  'PutBucketCors',
  'DeleteBucketPolicy',
  'PutBucketPolicy',
  'PutBucketReplicationConfiguration',
  'PutBucketLogging',
  'PutPublicAccessBlock',
  'DeletePublicAccessBlock',
];

const KEY_SUFFIXES = ['DOBNJ', '55KF', 'OFLT', 'XW5I', 'TMEZ'];
const SENSITIVE_KEY_PATTERN = /(secret|token|password|authorization|accessToken|secretAccessKey)/i;
const ACCESS_KEY_PATTERN = /\b((?:AKIA|ASIA|A3T[A-Z0-9])[A-Z0-9]{12,})\b/g;

function main() {
  const args = parseArgs(process.argv.slice(2));
  if (!args.workflows) {
    fail('Usage: node tools/audit-n8n-s3-workflows.mjs --workflows <path> [--credentials <path>] [--format markdown|csv] [--output <path>]');
  }

  const workflowInput = readJson(args.workflows);
  const credentialInput = args.credentials ? readJson(args.credentials) : workflowInput;
  const workflows = normalizeWorkflows(workflowInput);
  const credentials = normalizeCredentials(credentialInput);
  const findings = buildFindings(workflows, credentials);
  const format = args.format || 'markdown';
  const report = format === 'csv' ? toCsv(findings) : toMarkdown(findings, args.workflows, args.credentials);

  if (args.output) {
    fs.mkdirSync(path.dirname(path.resolve(args.output)), { recursive: true });
    fs.writeFileSync(args.output, report);
  } else {
    process.stdout.write(report);
  }
}

function parseArgs(argv) {
  const args = {};
  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];
    if (!arg.startsWith('--')) continue;
    const key = arg.slice(2);
    const next = argv[i + 1];
    if (!next || next.startsWith('--')) {
      args[key] = true;
    } else {
      args[key] = next;
      i += 1;
    }
  }
  return args;
}

function readJson(filePath) {
  try {
    return JSON.parse(fs.readFileSync(filePath, 'utf8').replace(/^\uFEFF/, ''));
  } catch (error) {
    fail(`Unable to read JSON from ${filePath}: ${error.message}`);
  }
}

function normalizeWorkflows(input) {
  if (Array.isArray(input)) return input;
  if (Array.isArray(input.workflows)) return input.workflows;
  if (input.data && Array.isArray(input.data.workflows)) return input.data.workflows;
  if (input.id && input.nodes) return [input];
  return [];
}

function normalizeCredentials(input) {
  if (Array.isArray(input)) return input;
  if (Array.isArray(input.credentials)) return input.credentials;
  if (input.data && Array.isArray(input.data.credentials)) return input.data.credentials;
  return [];
}

function buildFindings(workflows, credentials) {
  const credentialIndex = new Map();
  for (const credential of credentials) {
    for (const key of [credential.id, credential.name]) {
      if (key) credentialIndex.set(String(key), credential);
    }
  }

  const findings = [];
  for (const workflow of workflows) {
    for (const node of workflow.nodes || []) {
      const credentialRefs = getCredentialRefs(node);
      const relatedCredentials = credentialRefs
        .map((ref) => credentialIndex.get(ref.id) || credentialIndex.get(ref.name))
        .filter(Boolean);
      const scanTarget = {
        node,
        credentials: relatedCredentials.map((credential) => safeCredentialForScan(credential)),
      };
      const signals = collectSignals(scanTarget);
      if (!signals.length) continue;

      findings.push({
        workflowId: workflow.id || workflow.name || 'unknown-workflow',
        workflowName: workflow.name || workflow.id || 'Unnamed workflow',
        workflowActive: Boolean(workflow.active),
        nodeName: node.name || node.id || 'Unnamed node',
        nodeType: node.type || 'unknown',
        riskLevel: riskLevel(signals),
        signals,
        credentialRefs: credentialRefs.map((ref) => ref.name || ref.id).filter(Boolean),
      });
    }
  }

  const referencedCredentialKeys = new Set(findings.flatMap((finding) => finding.credentialRefs));
  for (const credential of credentials) {
    if (referencedCredentialKeys.has(credential.name) || referencedCredentialKeys.has(credential.id)) continue;
    const signals = collectSignals(safeCredentialForScan(credential));
    if (!signals.length) continue;
    findings.push({
      workflowId: 'credential-only',
      workflowName: 'Unreferenced credential metadata',
      workflowActive: false,
      nodeName: credential.name || credential.id || 'Unnamed credential',
      nodeType: credential.type || 'credential',
      riskLevel: riskLevel(signals),
      signals,
      credentialRefs: [credential.name || credential.id].filter(Boolean),
    });
  }

  return findings.sort((a, b) => scoreRisk(b.riskLevel) - scoreRisk(a.riskLevel) || a.workflowName.localeCompare(b.workflowName));
}

function getCredentialRefs(node) {
  const refs = [];
  for (const value of Object.values(node.credentials || {})) {
    if (!value || typeof value !== 'object') continue;
    refs.push({
      id: value.id ? String(value.id) : undefined,
      name: value.name ? String(value.name) : undefined,
    });
  }
  return refs;
}

function safeCredentialForScan(credential) {
  return {
    id: credential.id,
    name: credential.name,
    type: credential.type,
    data: credential.data,
  };
}

function collectSignals(value) {
  const signals = new Set();
  walk(value, [], (keyPath, item) => {
    if (item === null || item === undefined) return;
    const raw = String(item);
    const lower = raw.toLowerCase();
    const keyName = keyPath[keyPath.length - 1] || '';

    if (lower.includes('studenthub-uploads')) signals.add('bucket: studenthub-uploads');
    if (lower.includes('studenthub-public-anyone-can-upload-24hr-expiry')) signals.add('bucket: studenthub-public-anyone-can-upload-24hr-expiry');
    if (lower.includes('n8n-s3-access')) signals.add('credential: n8n-s3-access');
    if (lower.includes('aws')) signals.add('aws reference');
    if (lower.includes('s3')) signals.add('s3 reference');

    for (const event of BUCKET_ADMIN_EVENTS) {
      if (lower.includes(event.toLowerCase())) signals.add(`bucket-admin event: ${event}`);
    }

    for (const suffix of KEY_SUFFIXES) {
      if (raw.includes(suffix)) signals.add(`access-key suffix: ${suffix}`);
    }

    for (const match of raw.matchAll(ACCESS_KEY_PATTERN)) {
      signals.add(`access key: ${redact(match[1])}`);
    }

    if (SENSITIVE_KEY_PATTERN.test(keyName) && raw.length > 8) {
      signals.add(`${keyName}: ${redact(raw)}`);
    }
  });
  const result = Array.from(signals);
  const hasAwsS3Context = result.some((signal) => (
    signal.startsWith('bucket:') ||
    signal.startsWith('bucket-admin event:') ||
    signal.startsWith('access-key suffix:') ||
    signal.startsWith('access key:') ||
    signal === 'aws reference' ||
    signal === 's3 reference' ||
    signal === 'credential: n8n-s3-access'
  ));
  return hasAwsS3Context ? result : [];
}

function walk(value, keyPath, visit) {
  if (Array.isArray(value)) {
    value.forEach((item, index) => walk(item, keyPath.concat(String(index)), visit));
    return;
  }
  if (value && typeof value === 'object') {
    for (const [key, item] of Object.entries(value)) {
      walk(item, keyPath.concat(key), visit);
    }
    return;
  }
  visit(keyPath, value);
}

function riskLevel(signals) {
  if (signals.some((signal) => signal.startsWith('bucket-admin event:'))) return 'high';
  if (signals.some((signal) => signal.startsWith('bucket:') || signal.startsWith('access key:') || signal.startsWith('access-key suffix:'))) return 'medium';
  return 'low';
}

function scoreRisk(level) {
  return { high: 3, medium: 2, low: 1 }[level] || 0;
}

function toMarkdown(findings, workflowPath, credentialPath) {
  const lines = [
    '# n8n S3 Workflow Audit',
    '',
    `Workflow export: \`${workflowPath}\``,
    `Credential export: \`${credentialPath || workflowPath}\``,
    '',
    'This report is generated from offline maintainer-owned exports. It redacts access-key-looking values and secret-shaped fields.',
    '',
  ];

  if (!findings.length) {
    lines.push('No AWS/S3 workflow or credential findings were detected.', '');
    return lines.join('\n');
  }

  lines.push('| Risk | Workflow | Node | Type | Signals | Credential refs |', '|---|---|---|---|---|---|');
  for (const finding of findings) {
    lines.push([
      finding.riskLevel,
      `${finding.workflowName} (${finding.workflowId})`,
      finding.nodeName,
      finding.nodeType,
      finding.signals.join('<br>'),
      finding.credentialRefs.join(', ') || '-',
    ].map(markdownCell).join(' | ').replace(/^/, '| ').replace(/$/, ' |'));
  }
  lines.push('');
  return lines.join('\n');
}

function toCsv(findings) {
  const header = ['workflow_id', 'workflow_name', 'node_name', 'node_type', 'risk_level', 'signals', 'credential_refs'];
  const rows = findings.map((finding) => [
    finding.workflowId,
    finding.workflowName,
    finding.nodeName,
    finding.nodeType,
    finding.riskLevel,
    finding.signals.join('; '),
    finding.credentialRefs.join('; '),
  ]);
  return [header, ...rows].map((row) => row.map(csvCell).join(',')).join('\n') + '\n';
}

function markdownCell(value) {
  return String(value || '-').replace(/\|/g, '\\|').replace(/\n/g, '<br>');
}

function csvCell(value) {
  const text = String(value || '');
  return /[",\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
}

function redact(value) {
  const text = String(value);
  if (text.length <= 8) return '*'.repeat(text.length);
  return `${text.slice(0, 4)}${'*'.repeat(Math.min(12, text.length - 8))}${text.slice(-4)}`;
}

function fail(message) {
  console.error(message);
  process.exit(1);
}

main();
