#!/usr/bin/env node

import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {mkdtempSync, writeFileSync} from 'node:fs';
import {join} from 'node:path';
import {tmpdir} from 'node:os';
import {fileURLToPath} from 'node:url';

const root = fileURLToPath(new URL('..', import.meta.url));
const script = join(root, 'tools/audit-cloudtrail-s3-admin-events.mjs');
const dir = mkdtempSync(join(tmpdir(), 'cloudtrail-s3-admin-audit-'));
const jsonInput = join(dir, 'cloudtrail.json');
const csvInput = join(dir, 'cloudtrail.csv');
const invalidJsonInput = join(dir, 'invalid-cloudtrail.json');
const invalidJsonlInput = join(dir, 'invalid-cloudtrail.jsonl');

const riskyEvents = [
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

writeFileSync(
    jsonInput,
    JSON.stringify(
        {
            Records: [
                ...riskyEvents.map((eventName, index) => ({
                    eventTime: `2026-04-18T09:${String(index).padStart(2, '0')}:00Z`,
                    eventName,
                    userIdentity: {
                        userName: index % 2 === 0 ? 'railway-s3-access' : 'n8n-s3-access',
                        accessKeyId: `AKIA222222222222${String(index).padStart(4, '0')}`,
                    },
                    sourceIPAddress: `203.0.113.${index + 10}`,
                    userAgent: 'aws-cli/2.15.0',
                    awsRegion: 'eu-west-2',
                    requestParameters: {
                        bucketName: index === 3 ? 'wallet-uploads' : 'studenthub-uploads',
                    },
                    ...(index === 3 ? {errorCode: 'AccessDenied'} : {}),
                })),
                {
                    eventTime: '2026-04-18T09:05:00Z',
                    eventName: 'PutObject',
                    userIdentity: {
                        userName: 'railway-s3-access',
                        accessKeyId: 'AKIA222222222222WCUM',
                    },
                    requestParameters: {
                        bucketName: 'studenthub-uploads',
                    },
                },
            ],
        },
        null,
        2,
    ),
);

writeFileSync(
    csvInput,
    [
        'eventTime,eventName,userName,accessKeyId,sourceIPAddress,userAgent,bucketName,region,errorCode',
        '2026-04-19T08:00:00Z,PutBucketCors,n8n-s3-access,AKIA222222222222NANA,192.0.2.22,n8n,studenthub-public-anyone-can-upload-24hr-expiry,eu-west-2,',
    ].join('\n'),
);

writeFileSync(invalidJsonInput, JSON.stringify({Records: {eventName: 'PutBucketPolicy'}}));
writeFileSync(invalidJsonlInput, '{"eventName": "PutBucketPolicy"}\n{"eventName":');

const markdown = execFileSync(process.execPath, [script, '--input', jsonInput, '--input', csvInput], {encoding: 'utf8'});

assert.match(markdown, /Matching bucket-admin events: 10/);
assert.match(markdown, /Critical\/high events: 4/);
for (const eventName of riskyEvents) {
    const expectedCount = eventName === 'PutBucketCors' ? 2 : 1;
    assert.match(markdown, new RegExp(`${eventName}: ${expectedCount}`));
}
assert.match(markdown, /railway-s3-access: 5/);
assert.match(markdown, /n8n-s3-access: 5/);
assert.match(markdown, /wallet-uploads/);
assert.match(markdown, /watched service user; non-StudentHub bucket; failed with AccessDenied/);
assert.doesNotMatch(markdown, /AKIA2222222222220000/);
assert.match(markdown, /\|2026-04-18T09:00:00Z\|critical\|PutBucketLifecycleConfiguration\|railway-s3-access\|0000\|/);

const csv = execFileSync(process.execPath, [script, '--input', jsonInput, '--format', 'csv'], {encoding: 'utf8'});

assert.match(csv, /event_time,severity,event_name,user_name,access_key_suffix/);
assert.match(csv, /2026-04-18T09:03:00Z,low,DeleteBucketPolicy,n8n-s3-access,0003/);
assert.doesNotMatch(csv, /AKIA2222222222220003/);
assert.doesNotMatch(csv, /PutObject/);

const csvFromCsvInput = execFileSync(process.execPath, [script, '--input', csvInput, '--format', 'csv'], {encoding: 'utf8'});

assert.match(csvFromCsvInput, /2026-04-19T08:00:00Z,medium,PutBucketCors,n8n-s3-access,NANA/);
assert.doesNotMatch(csvFromCsvInput, /AKIA222222222222NANA/);

assert.throws(() => execFileSync(process.execPath, [script, '--input', invalidJsonInput], {encoding: 'utf8', stdio: 'pipe'}), /Records array/);
assert.throws(() => execFileSync(process.execPath, [script, '--input', invalidJsonlInput], {encoding: 'utf8', stdio: 'pipe'}), /Invalid JSONL.*line 2/);

console.log('CloudTrail S3 admin audit helper check passed.');
