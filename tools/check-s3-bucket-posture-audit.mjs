#!/usr/bin/env node

import { execFileSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const auditScript = path.join(__dirname, "audit-s3-bucket-posture.mjs");
const fixture = path.join(__dirname, "fixtures", "s3-bucket-posture.sample.json");

function runAudit(args = []) {
  try {
    return execFileSync(process.execPath, [auditScript, fixture, ...args], {
      encoding: "utf8",
      stdio: ["ignore", "pipe", "pipe"],
    });
  } catch (error) {
    if (error.status === 2) {
      return error.stdout;
    }
    throw error;
  }
}

const markdown = runAudit();

for (const expected of [
  "studenthub-uploads",
  "Versioning is not enabled",
  "PressureDelete3Days",
  "CORS allows wildcard origins",
  "Missing ownership tags: environment",
]) {
  if (!markdown.includes(expected)) {
    throw new Error(`Markdown audit output is missing: ${expected}`);
  }
}

for (const forbidden of ["123456789012", "AKIAIOSFODNN7EXAMPLE"]) {
  if (markdown.includes(forbidden)) {
    throw new Error(`Markdown audit output leaked private-looking value: ${forbidden}`);
  }
}

const csv = runAudit(["--format", "csv"]);
if (!csv.startsWith('"severity","bucket","check","finding","remediation","source_file"')) {
  throw new Error("CSV audit output should include the expected header");
}
if (!csv.includes('"critical","studenthub-uploads","lifecycle"')) {
  throw new Error("CSV audit output should include the critical lifecycle finding");
}
if (csv.includes("123456789012") || csv.includes("AKIAIOSFODNN7EXAMPLE")) {
  throw new Error("CSV audit output leaked private-looking values");
}

let missingOptionError;
try {
  execFileSync(process.execPath, [auditScript, fixture, "--format"], {
    encoding: "utf8",
    stdio: ["ignore", "pipe", "pipe"],
  });
} catch (error) {
  missingOptionError = error;
}

if (!missingOptionError) {
  throw new Error("Missing option values should fail");
}

const stderr = String(missingOptionError.stderr ?? "");
if (!stderr.includes("--format requires a value")) {
  throw new Error("Missing option value errors should name the flag");
}

console.log("S3 bucket posture audit check passed.");
