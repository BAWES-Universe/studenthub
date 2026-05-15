#!/usr/bin/env node

import { readFileSync } from 'node:fs';
import { basename } from 'node:path';

const CURRENT_PREFIX = 'photos/';
const LEGACY_PREFIX = 'candidate-civil-id/';

function printUsage() {
  console.log(`Usage:
  node tools/audit-civil-id-s3-objects.mjs --candidates candidates.csv --permanent-objects permanent.txt [--temp-objects temp.txt] [--format markdown|csv] [--emit-copy-commands]

Inputs:
  --candidates         CSV export with candidate_id, side, filename or expected_s3_key columns
  --permanent-objects  Text or CSV-style object inventory for studenthub-uploads
  --temp-objects       Optional object inventory for studenthub-public-anyone-can-upload-24hr-expiry

The script does not call AWS. It classifies records using offline exports only.`);
}

function parseArgs(argv) {
  const args = {
    format: 'markdown',
    emitCopyCommands: false,
  };

  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];

    if (arg === '--help' || arg === '-h') {
      args.help = true;
    } else if (arg === '--emit-copy-commands') {
      args.emitCopyCommands = true;
    } else if (arg.startsWith('--')) {
      const key = arg.slice(2).replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
      const value = argv[i + 1];

      if (!value || value.startsWith('--')) {
        throw new Error(`${arg} requires a value`);
      }

      args[key] = value;
      i += 1;
    } else {
      throw new Error(`Unexpected argument: ${arg}`);
    }
  }

  if (args.help) {
    return args;
  }

  if (!args.candidates || !args.permanentObjects) {
    throw new Error('--candidates and --permanent-objects are required');
  }

  if (!['markdown', 'csv'].includes(args.format)) {
    throw new Error('--format must be markdown or csv');
  }

  return args;
}

function parseCsvLine(line) {
  const cells = [];
  let cell = '';
  let quoted = false;

  for (let i = 0; i < line.length; i += 1) {
    const char = line[i];
    const next = line[i + 1];

    if (quoted && char === '"' && next === '"') {
      cell += '"';
      i += 1;
    } else if (char === '"') {
      quoted = !quoted;
    } else if (!quoted && char === ',') {
      cells.push(cell);
      cell = '';
    } else {
      cell += char;
    }
  }

  cells.push(cell);
  return cells.map((value) => value.trim());
}

function readLines(file) {
  return readFileSync(file, 'utf8')
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line && !line.startsWith('#'));
}

function readObjectKeys(file) {
  if (!file) {
    return new Set();
  }

  const keys = new Set();

  for (const line of readLines(file)) {
    for (const cell of parseCsvLine(line)) {
      const value = cell.trim().replace(/^"|"$/g, '');

      if (value.startsWith(CURRENT_PREFIX) || value.startsWith(LEGACY_PREFIX)) {
        keys.add(value);
      }
    }
  }

  return keys;
}

function readCandidateRows(file) {
  const lines = readLines(file);

  if (lines.length === 0) {
    return [];
  }

  const headers = parseCsvLine(lines[0]).map((header) => header.toLowerCase());
  const rows = [];

  for (const line of lines.slice(1)) {
    const values = parseCsvLine(line);
    const row = Object.fromEntries(headers.map((header, index) => [header, values[index] ?? '']));
    const candidateId = row.candidate_id || row.candidateid || row.id;
    const side = row.side || row.photo_side || row.type || 'unknown';
    const rawFilename = row.filename || row.expected_s3_key || row.s3_key || row.key;
    const filename = normalizeCivilIdFilename(rawFilename);

    if (!candidateId || !filename) {
      continue;
    }

    rows.push({
      candidateId,
      side,
      filename,
      updatedAt: row.candidate_updated_at || row.updated_at || '',
      currentKey: `${CURRENT_PREFIX}${filename}`,
      legacyKey: `${LEGACY_PREFIX}${filename}`,
    });
  }

  return rows;
}

function normalizeCivilIdFilename(value) {
  if (!value) {
    return '';
  }

  const normalized = value.trim().replace(/^"|"$/g, '').replace(/^\/+/, '');

  if (!normalized) {
    return '';
  }

  if (normalized.startsWith(CURRENT_PREFIX)) {
    return normalized.slice(CURRENT_PREFIX.length);
  }

  if (normalized.startsWith(LEGACY_PREFIX)) {
    return normalized.slice(LEGACY_PREFIX.length);
  }

  return basename(normalized);
}

function classify(row, permanentKeys, tempKeys) {
  if (permanentKeys.has(row.currentKey)) {
    return {
      status: 'present',
      action: 'No action needed',
      sourceKey: row.currentKey,
      destinationKey: row.currentKey,
    };
  }

  if (permanentKeys.has(row.legacyKey)) {
    return {
      status: 'recover_from_legacy',
      action: 'Copy legacy permanent object to photos prefix',
      sourceBucket: 'studenthub-uploads',
      sourceKey: row.legacyKey,
      destinationBucket: 'studenthub-uploads',
      destinationKey: row.currentKey,
    };
  }

  if (tempKeys.has(row.currentKey) || tempKeys.has(row.filename)) {
    const sourceKey = tempKeys.has(row.currentKey) ? row.currentKey : row.filename;

    return {
      status: 'recover_from_temp',
      action: 'Copy temp object to permanent photos prefix',
      sourceBucket: 'studenthub-public-anyone-can-upload-24hr-expiry',
      sourceKey,
      destinationBucket: 'studenthub-uploads',
      destinationKey: row.currentKey,
    };
  }

  return {
    status: 'missing',
    action: 'Request re-upload; do not clear DB field before audit is complete',
    sourceKey: '',
    destinationKey: row.currentKey,
  };
}

function summarize(results) {
  return results.reduce((summary, result) => {
    summary[result.status] = (summary[result.status] ?? 0) + 1;
    return summary;
  }, {});
}

function shellQuote(value) {
  return `'${String(value).replaceAll("'", "'\"'\"'")}'`;
}

function copyCommand(result) {
  if (!result.sourceBucket || !result.destinationBucket) {
    return '';
  }

  return [
    'aws',
    's3',
    'cp',
    shellQuote(`s3://${result.sourceBucket}/${result.sourceKey}`),
    shellQuote(`s3://${result.destinationBucket}/${result.destinationKey}`),
    '--only-show-errors',
  ].join(' ');
}

function toCsv(results, includeCommands) {
  const headers = [
    'candidate_id',
    'side',
    'filename',
    'status',
    'action',
    'source_key',
    'destination_key',
    'candidate_updated_at',
  ];

  if (includeCommands) {
    headers.push('copy_command');
  }

  const lines = [headers.join(',')];

  for (const result of results) {
    const cells = [
      result.candidateId,
      result.side,
      result.filename,
      result.status,
      result.action,
      result.sourceKey,
      result.destinationKey,
      result.updatedAt,
    ];

    if (includeCommands) {
      cells.push(copyCommand(result));
    }

    lines.push(cells.map(csvEscape).join(','));
  }

  return lines.join('\n');
}

function csvEscape(value) {
  const stringValue = String(value ?? '');

  if (/[",\n]/.test(stringValue)) {
    return `"${stringValue.replaceAll('"', '""')}"`;
  }

  return stringValue;
}

function toMarkdown(results, includeCommands) {
  const summary = summarize(results);
  const lines = [
    '# Civil ID S3 Recovery Audit',
    '',
    'This report is generated from offline database and S3 inventory exports. It does not prove live AWS state unless the exports were captured after the latest upload remediation.',
    '',
    '## Summary',
    '',
    `- Present in permanent photos prefix: ${summary.present ?? 0}`,
    `- Recoverable from legacy permanent prefix: ${summary.recover_from_legacy ?? 0}`,
    `- Recoverable from temp bucket export: ${summary.recover_from_temp ?? 0}`,
    `- Missing from all supplied exports: ${summary.missing ?? 0}`,
    '',
    '## Findings',
    '',
    '| Candidate | Side | Filename | Status | Action | Source | Destination |',
    '|-|-|-|-|-|-|-|',
  ];

  for (const result of results) {
    lines.push([
      result.candidateId,
      result.side,
      result.filename,
      result.status,
      result.action,
      result.sourceKey || '-',
      result.destinationKey || '-',
    ].map(markdownCell).join('|').replace(/^/, '|').replace(/$/, '|'));

    if (includeCommands) {
      const command = copyCommand(result);

      if (command) {
        lines.push('');
        lines.push(`Copy command for candidate ${result.candidateId} ${result.side}:`);
        lines.push('');
        lines.push('```bash');
        lines.push(command);
        lines.push('```');
        lines.push('');
      }
    }
  }

  return lines.join('\n');
}

function markdownCell(value) {
  return String(value ?? '').replaceAll('|', '\\|');
}

function main() {
  try {
    const args = parseArgs(process.argv.slice(2));

    if (args.help) {
      printUsage();
      return;
    }

    const permanentKeys = readObjectKeys(args.permanentObjects);
    const tempKeys = readObjectKeys(args.tempObjects);
    const rows = readCandidateRows(args.candidates);
    const results = rows.map((row) => ({ ...row, ...classify(row, permanentKeys, tempKeys) }));

    if (args.format === 'csv') {
      console.log(toCsv(results, args.emitCopyCommands));
    } else {
      console.log(toMarkdown(results, args.emitCopyCommands));
    }
  } catch (error) {
    console.error(`Error: ${error.message}`);
    console.error('');
    printUsage();
    process.exitCode = 1;
  }
}

main();
