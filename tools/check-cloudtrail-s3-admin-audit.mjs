#!/usr/bin/env node

import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {mkdtempSync, writeFileSync} from 'node:fs';
import {join} from 'node:path';
import {tmpdir} from 'node:os';

const root = new URL('..', import.meta.url).pathname;
const script = join(root, 'tools/audit-cloudtrail-s3-admin-events.mjs');
const dir = mkdtempSync(join(tmpdir(), 'cloudtrail-s3-admin-audit-'));
const jsonInput = join(dir, 'cloudtrail.json');
const csvInput = join(dir, 'cloudtrail.csv');

writeFileSync(
    jsonInput,
    JSON.stringify(
        {
            Records: [
                {
                    eventTime: '2026-04-18T09:00:00Z',
                    eventName: 'PutBucketLifecycleConfiguration',
                    userIdentity: {
                        userName: 'railway-s3-access',
                        accessKeyId: 'AKIA000000000000WCUM',
                    },
                    sourceIPAddress: '203.0.113.10',
                    userAgent: 'aws-cli/2.15.0',
                    awsRegion: 'eu-west-2',
                    requestParameters: {
                        bucketName: 'studenthub-uploads',
                    },
                },
                {
                    eventTime: '2026-04-18T09:05:00Z',
                    eventName: 'PutObject',
                    userIdentity: {
                        userName: 'railway-s3-access',
                        accessKeyId: 'AKIA000000000000WCUM',
                    },
                    requestParameters: {
                        bucketName: 'studenthub-uploads',
                    },
                },
                {
                    eventTime: '2026-04-18T10:00:00Z',
                    eventName: 'DeleteBucketPolicy',
                    userIdentity: {
                        userName: 'mediaconverter',
                        accessKeyId: 'AKIA000000000000OFLT',
                    },
                    sourceIPAddress: '198.51.100.9',
                    userAgent: 'console.amazonaws.com',
                    awsRegion: 'eu-west-2',
                    requestParameters: {
                        bucketName: 'wallet-uploads',
                    },
                    errorCode: 'AccessDenied',
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
        '2026-04-19T08:00:00Z,PutBucketCors,n8n-s3-access,AKIA000000000000N8N1,192.0.2.22,n8n,studenthub-public-anyone-can-upload-24hr-expiry,eu-west-2,',
    ].join('\n'),
);

const markdown = execFileSync(process.execPath, [script, '--input', jsonInput, '--input', csvInput], {encoding: 'utf8'});

assert.match(markdown, /Matching bucket-admin events: 3/);
assert.match(markdown, /Critical\/high events: 1/);
assert.match(markdown, /PutBucketLifecycleConfiguration: 1/);
assert.match(markdown, /DeleteBucketPolicy: 1/);
assert.match(markdown, /PutBucketCors: 1/);
assert.match(markdown, /railway-s3-access: 1/);
assert.match(markdown, /mediaconverter: 1/);
assert.match(markdown, /n8n-s3-access: 1/);
assert.match(markdown, /wallet-uploads/);
assert.match(markdown, /watched service user; non-StudentHub bucket; failed with AccessDenied/);
assert.doesNotMatch(markdown, /AKIA000000000000WCUM/);
assert.match(markdown, /\|2026-04-18T09:00:00Z\|critical\|PutBucketLifecycleConfiguration\|railway-s3-access\|WCUM\|/);

const csv = execFileSync(process.execPath, [script, '--input', jsonInput, '--format', 'csv'], {encoding: 'utf8'});

assert.match(csv, /event_time,severity,event_name,user_name,access_key_suffix/);
assert.match(csv, /2026-04-18T10:00:00Z,low,DeleteBucketPolicy,mediaconverter,OFLT/);
assert.doesNotMatch(csv, /AKIA000000000000OFLT/);
assert.doesNotMatch(csv, /PutObject/);

console.log('CloudTrail S3 admin audit helper check passed.');
