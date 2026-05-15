#!/usr/bin/env node

import {readFileSync} from 'node:fs';
import {basename} from 'node:path';

const SUSPICIOUS_EVENTS = new Set([
    'PutBucketLifecycleConfiguration',
    'DeleteBucketCors',
    'PutBucketCors',
    'DeleteBucketPolicy',
    'PutBucketPolicy',
    'PutBucketReplicationConfiguration',
    'PutBucketLogging',
    'PutPublicAccessBlock',
    'DeletePublicAccessBlock',
]);

const DEFAULT_USERS = new Set(['railway-s3-access', 'n8n-s3-access', 'mediaconverter']);
const ACCESS_KEY_PATTERN = /AKIA[0-9A-Z]{12,20}/g;
const SECRET_SHAPED_PATTERN = /(?<![A-Za-z0-9/+=])[A-Za-z0-9/+=]{32,}(?![A-Za-z0-9/+=])/g;

function printUsage() {
    console.log(`Usage:
  node tools/audit-cloudtrail-s3-admin-events.mjs --input cloudtrail.json [--input events.csv] [--watch-user railway-s3-access] [--format markdown|csv]

Inputs:
  --input       CloudTrail JSON export, CloudTrail Records JSON, JSONL, or CSV export
  --watch-user  IAM user to highlight. Can be repeated. Defaults to railway-s3-access, n8n-s3-access, mediaconverter

The script does not call AWS. It summarizes offline exports only and redacts credential-shaped values from output.`);
}

function parseArgs(argv) {
    const args = {
        inputs: [],
        watchUsers: new Set(DEFAULT_USERS),
        format: 'markdown',
    };

    for (let i = 0; i < argv.length; i += 1) {
        const arg = argv[i];

        if (arg === '--help' || arg === '-h') {
            args.help = true;
        } else if (arg === '--input') {
            const value = argv[i + 1];
            if (!value || value.startsWith('--')) {
                throw new Error('--input requires a value');
            }
            args.inputs.push(value);
            i += 1;
        } else if (arg === '--watch-user') {
            const value = argv[i + 1];
            if (!value || value.startsWith('--')) {
                throw new Error('--watch-user requires a value');
            }
            if (args.watchUsers === DEFAULT_USERS) {
                args.watchUsers = new Set(DEFAULT_USERS);
            }
            args.watchUsers.add(value);
            i += 1;
        } else if (arg === '--format') {
            const value = argv[i + 1];
            if (!['markdown', 'csv'].includes(value)) {
                throw new Error('--format must be markdown or csv');
            }
            args.format = value;
            i += 1;
        } else {
            throw new Error(`Unexpected argument: ${arg}`);
        }
    }

    if (!args.help && args.inputs.length === 0) {
        throw new Error('At least one --input file is required');
    }

    return args;
}

function readEvents(file) {
    const raw = readFileSync(file, 'utf8').trim();
    if (!raw) {
        return [];
    }

    if (raw.startsWith('{') || raw.startsWith('[')) {
        return parseJsonEvents(raw, file);
    }

    return parseCsvEvents(raw, file);
}

function parseJsonEvents(raw, file) {
    try {
        const parsed = JSON.parse(raw);
        const records = Array.isArray(parsed) ? parsed : parsed.Records;

        if (!Array.isArray(records)) {
            throw new Error('JSON input must be an array or an object with a Records array');
        }

        return records.map((event) => normalizeEvent(event, file));
    } catch (error) {
        return raw
            .split(/\r?\n/)
            .filter(Boolean)
            .map((line) => normalizeEvent(JSON.parse(line), file));
    }
}

function parseCsvEvents(raw, file) {
    const lines = raw.split(/\r?\n/).filter(Boolean);
    if (lines.length === 0) {
        return [];
    }

    const headers = parseCsvLine(lines[0]).map((header) => header.trim());
    return lines.slice(1).map((line) => {
        const values = parseCsvLine(line);
        const row = Object.fromEntries(headers.map((header, index) => [header, values[index] ?? '']));
        return normalizeEvent(row, file);
    });
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

function normalizeEvent(event, file) {
    const requestParameters = parseMaybeJson(event.requestParameters ?? event['request parameters'] ?? {});
    const userIdentity = parseMaybeJson(event.userIdentity ?? event['user identity'] ?? {});
    const resources = parseMaybeJson(event.resources ?? []);
    const eventName = event.eventName ?? event['event name'] ?? event.EventName ?? '';
    const userName = event.userName ?? userIdentity.userName ?? userIdentity.sessionContext?.sessionIssuer?.userName ?? '';
    const accessKeyId = event.accessKeyId ?? userIdentity.accessKeyId ?? event['access key id'] ?? '';
    const bucketName = event.bucketName ?? requestParameters.bucketName ?? requestParameters.bucket ?? findBucketFromResources(resources) ?? '';

    return {
        sourceFile: basename(file),
        eventTime: event.eventTime ?? event['event time'] ?? event.EventTime ?? '',
        eventName,
        userName,
        accessKeySuffix: suffix(accessKeyId),
        accessKeyId,
        sourceIPAddress: event.sourceIPAddress ?? event['source ip address'] ?? event.SourceIPAddress ?? '',
        userAgent: event.userAgent ?? event['user agent'] ?? event.UserAgent ?? '',
        bucketName,
        region: event.awsRegion ?? event.region ?? event.Region ?? '',
        errorCode: event.errorCode ?? event['error code'] ?? '',
        raw: event,
    };
}

function parseMaybeJson(value) {
    if (!value || typeof value !== 'string') {
        return value;
    }

    try {
        return JSON.parse(value);
    } catch {
        return value;
    }
}

function findBucketFromResources(resources) {
    if (!Array.isArray(resources)) {
        return '';
    }

    for (const resource of resources) {
        const name = resource?.ARN ?? resource?.arn ?? resource?.resourceName ?? resource?.name;
        const match = typeof name === 'string' ? name.match(/arn:aws:s3:::([^/]+)/) : null;
        if (match?.[1]) {
            return match[1];
        }
    }

    return '';
}

function suffix(accessKeyId) {
    return typeof accessKeyId === 'string' && accessKeyId.length >= 4 ? accessKeyId.slice(-4) : '';
}

function classifyEvents(events, watchUsers) {
    return events
        .filter((event) => SUSPICIOUS_EVENTS.has(event.eventName))
        .map((event) => ({
            ...event,
            watchedUser: watchUsers.has(event.userName),
            severity: severityFor(event),
            reviewNote: reviewNoteFor(event, watchUsers),
        }))
        .sort((a, b) => String(a.eventTime).localeCompare(String(b.eventTime)));
}

function severityFor(event) {
    if (event.errorCode) {
        return 'low';
    }

    if (event.eventName === 'PutBucketLifecycleConfiguration') {
        return 'critical';
    }

    if (['PutBucketPolicy', 'DeleteBucketPolicy', 'PutPublicAccessBlock', 'DeletePublicAccessBlock'].includes(event.eventName)) {
        return 'high';
    }

    return 'medium';
}

function reviewNoteFor(event, watchUsers) {
    const notes = [];

    if (watchUsers.has(event.userName)) {
        notes.push('watched service user');
    }

    if (event.bucketName && !event.bucketName.startsWith('studenthub-')) {
        notes.push('non-StudentHub bucket');
    }

    if (event.errorCode) {
        notes.push(`failed with ${event.errorCode}`);
    }

    return notes.join('; ') || 'review source IP and automation owner';
}

function summarize(events) {
    const summary = {
        total: events.length,
        byEvent: new Map(),
        byUser: new Map(),
        byBucket: new Map(),
        criticalOrHigh: 0,
    };

    for (const event of events) {
        increment(summary.byEvent, event.eventName);
        increment(summary.byUser, event.userName || '(unknown)');
        increment(summary.byBucket, event.bucketName || '(unknown)');
        if (['critical', 'high'].includes(event.severity)) {
            summary.criticalOrHigh += 1;
        }
    }

    return summary;
}

function increment(map, key) {
    map.set(key, (map.get(key) ?? 0) + 1);
}

function toCsv(events) {
    const headers = ['event_time', 'severity', 'event_name', 'user_name', 'access_key_suffix', 'source_ip', 'user_agent', 'bucket', 'region', 'error_code', 'review_note', 'source_file'];
    return [
        headers.join(','),
        ...events.map((event) =>
            [
                event.eventTime,
                event.severity,
                event.eventName,
                event.userName,
                event.accessKeySuffix,
                event.sourceIPAddress,
                event.userAgent,
                event.bucketName,
                event.region,
                event.errorCode,
                event.reviewNote,
                event.sourceFile,
            ]
                .map((value) => csvEscape(redact(value)))
                .join(','),
        ),
    ].join('\n');
}

function toMarkdown(events) {
    const summary = summarize(events);
    const lines = [
        '# CloudTrail S3 Admin Event Audit',
        '',
        'This report is generated from offline CloudTrail exports. It does not call AWS, mutate IAM, or include full access keys.',
        '',
        '## Summary',
        '',
        `- Matching bucket-admin events: ${summary.total}`,
        `- Critical/high events: ${summary.criticalOrHigh}`,
        '',
        '### By Event',
        '',
        ...mapToBullets(summary.byEvent),
        '',
        '### By User',
        '',
        ...mapToBullets(summary.byUser),
        '',
        '### By Bucket',
        '',
        ...mapToBullets(summary.byBucket),
        '',
        '## Events',
        '',
        '| Time | Severity | Event | User | Key | Source IP | Bucket | Region | Error | Note |',
        '|-|-|-|-|-|-|-|-|-|-|',
    ];

    for (const event of events) {
        lines.push(
            [
                event.eventTime,
                event.severity,
                event.eventName,
                event.userName || '-',
                event.accessKeySuffix || '-',
                event.sourceIPAddress || '-',
                event.bucketName || '-',
                event.region || '-',
                event.errorCode || '-',
                event.reviewNote,
            ]
                .map((value) => markdownCell(redact(value)))
                .join('|')
                .replace(/^/, '|')
                .replace(/$/, '|'),
        );
    }

    return lines.join('\n');
}

function mapToBullets(map) {
    if (map.size === 0) {
        return ['- None'];
    }

    return [...map.entries()]
        .sort(([, a], [, b]) => b - a)
        .map(([key, count]) => `- ${redact(key)}: ${count}`);
}

function redact(value) {
    return String(value ?? '')
        .replace(ACCESS_KEY_PATTERN, (match) => `AKIA…${match.slice(-4)}`)
        .replace(SECRET_SHAPED_PATTERN, '[REDACTED]');
}

function csvEscape(value) {
    const stringValue = String(value ?? '');

    if (/[",\n]/.test(stringValue)) {
        return `"${stringValue.replaceAll('"', '""')}"`;
    }

    return stringValue;
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

        const events = args.inputs.flatMap((input) => readEvents(input));
        const suspiciousEvents = classifyEvents(events, args.watchUsers);
        console.log(args.format === 'csv' ? toCsv(suspiciousEvents) : toMarkdown(suspiciousEvents));
    } catch (error) {
        console.error(`Error: ${error.message}`);
        console.error('');
        printUsage();
        process.exitCode = 1;
    }
}

main();
