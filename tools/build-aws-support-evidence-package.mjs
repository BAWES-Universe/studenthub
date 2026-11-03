#!/usr/bin/env node

import fs from "node:fs";

const [, , manifestPath] = process.argv;

if (!manifestPath) {
  console.error(
    "Usage: node tools/build-aws-support-evidence-package.mjs <manifest.json>",
  );
  process.exit(1);
}

let manifest;
try {
  manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
} catch (error) {
  console.error(`Failed to load manifest at ${manifestPath}: ${error.message}`);
  process.exit(1);
}

function redact(value) {
  if (value == null) {
    return "";
  }

  return String(value)
    .replace(/\b\d{12}\b/g, "[aws-account-id-redacted]")
    .replace(/\bA(KIA|SIA|GPA|IDA)[A-Z0-9]{16}\b/g, "[aws-access-key-redacted]")
    .replace(
      /\b(?=[A-Za-z0-9+/=_-]{32,}\b)(?=.*[A-Z])(?=.*[a-z])(?=.*\d)[A-Za-z0-9+/=_-]{32,}\b/g,
      "[secret-like-value-redacted]",
    );
}

function line(value) {
  return redact(value || "not provided")
    .replace(/\|/g, "\\|")
    .replace(/\r?\n/g, " ");
}

function list(values) {
  if (!Array.isArray(values) || values.length === 0) {
    return "- None provided\n";
  }

  return values.map((value) => `- ${line(value)}`).join("\n") + "\n";
}

function table(headers, rows) {
  const safeRows = Array.isArray(rows) ? rows : [];
  const header = `| ${headers.join(" | ")} |`;
  const divider = `| ${headers.map(() => "---").join(" | ")} |`;
  const body = safeRows.map(
    (row) => `| ${headers.map((key) => line(row[key])).join(" | ")} |`,
  );

  return [header, divider, ...body].join("\n") + "\n";
}

function envTable(rows) {
  const safeRows = Array.isArray(rows) ? rows : [];
  return table(
    ["service", "names", "evidence"],
    safeRows.map((row) => ({
      service: row.service,
      names: Array.isArray(row.names) ? row.names.join(", ") : row.names,
      evidence: row.evidence,
    })),
  );
}

const incident = manifest.incident || {};

const sections = [
  `# ${line(incident.title || "AWS Support Evidence Package")}`,
  "",
  `Prepared by: ${line(incident.preparedBy)}`,
  `Prepared at: ${line(incident.preparedAt)}`,
  `Private evidence folder: ${line(incident.privateEvidenceFolder)}`,
  "",
  "## Deleted Inactive Keys",
  table(["suffix", "iamUser", "status", "deletedAt", "evidence"], manifest.deletedKeys),
  "## Rotated Or Deactivated Keys",
  table(["suffix", "iamUser", "replacement", "status", "evidence"], manifest.rotatedKeys),
  "## Replacement Environment Variables",
  envTable(manifest.environmentVariables),
  "## Bucket Controls",
  table(["bucket", "control", "status", "evidence"], manifest.bucketControls),
  "## Smoke Tests",
  table(["name", "status", "evidence"], manifest.smokeTests),
  "## CloudTrail Review Summary",
  table(
    [
      "eventTime",
      "eventName",
      "userName",
      "accessKeySuffix",
      "sourceIPAddress",
      "userAgent",
      "bucketName",
      "result",
    ],
    manifest.cloudTrail,
  ),
  "## IAM Review Summary",
  table(["iamUser", "status", "evidence"], manifest.iamReviews),
  "## Support Notes",
  list(manifest.supportNotes),
  "## Public Safety Checklist",
  "- Full access keys are redacted or represented by suffix only.",
  "- Secret-like values are redacted.",
  "- AWS account IDs are redacted.",
  "- Candidate Civil ID images, values, phone numbers, raw exports, and payment/tax data are not included.",
  "",
];

process.stdout.write(sections.join("\n"));
