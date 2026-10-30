#!/usr/bin/env node

import { execFileSync } from "node:child_process";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const auditScript = path.join(__dirname, "audit-cloudtrail-s3-admin-events.mjs");
const fixture = path.join(__dirname, "fixtures", "cloudtrail-s3-admin-events.sample.json");

const markdown = execFileSync(process.execPath, [auditScript, fixture], {
  encoding: "utf8",
});

const requiredSnippets = [
  "Matching events: 2",
  "PutBucketLifecycleConfiguration",
  "DeleteBucketCors",
  "railway-s3-access",
  "n8n-s3-access",
  "ODY2",
  "N8N1",
];

for (const snippet of requiredSnippets) {
  if (!markdown.includes(snippet)) {
    throw new Error(`Expected markdown audit output to include: ${snippet}`);
  }
}

const csv = execFileSync(process.execPath, [auditScript, fixture, "--format", "csv"], {
  encoding: "utf8",
});

if (!csv.startsWith("eventTime,eventName,userName,accessKeyIdSuffix")) {
  throw new Error("CSV output is missing the expected header");
}

if (csv.includes("PutObject") || csv.includes("unrelated-audit-bucket")) {
  throw new Error("Audit output included events outside the configured scope");
}

const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), "studenthub-cloudtrail-"));
const invalidFixture = path.join(tempDir, "invalid.json");
fs.writeFileSync(invalidFixture, "{not-json");

let invalidRunError;
try {
  execFileSync(process.execPath, [auditScript, invalidFixture], {
    encoding: "utf8",
    stdio: ["ignore", "pipe", "pipe"],
  });
} catch (error) {
  invalidRunError = error;
} finally {
  fs.rmSync(tempDir, { recursive: true, force: true });
}

if (!invalidRunError) {
  throw new Error("Expected invalid JSON input to fail");
}

const stderr = String(invalidRunError.stderr ?? "");
if (!stderr.includes("Failed to parse JSON") || !stderr.includes(invalidFixture)) {
  throw new Error("Invalid JSON errors should include the failing file path");
}

console.log("CloudTrail S3 admin audit check passed.");
