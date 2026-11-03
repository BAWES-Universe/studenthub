#!/usr/bin/env node

import { execFileSync } from "node:child_process";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";

const fixturePath = "tools/fixtures/aws-support-evidence-package.sample.json";
const manifest = JSON.parse(fs.readFileSync(fixturePath, "utf8"));
manifest.supportNotes = [
  ...(manifest.supportNotes || []),
  `Synthetic redaction probe: key ${"AKIA"}${"ABCDEFGHIJKLMNOP"} must not render.`,
  "Synthetic Markdown probe: support note with | pipe and\nnewline must remain on one bullet.",
];
manifest.bucketControls = [
  ...(manifest.bucketControls || []),
  {
    bucket: "studenthub-uploads",
    control: "demo control with | pipe",
    status: "complete",
    evidence: "screenshots/multiline\npath.png",
  },
];

const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), "aws-support-evidence-"));
try {
  const tmpManifest = path.join(tmpDir, "manifest.json");
  fs.writeFileSync(tmpManifest, JSON.stringify(manifest, null, 2));

  const output = execFileSync(process.execPath, [
    "tools/build-aws-support-evidence-package.mjs",
    tmpManifest,
  ], { encoding: "utf8", timeout: 10000 });

  const required = [
    "Deleted Inactive Keys",
    "Rotated Or Deactivated Keys",
    "Replacement Environment Variables",
    "Bucket Controls",
    "Smoke Tests",
    "CloudTrail Review Summary",
    "IAM Review Summary",
    "Public Safety Checklist",
    "FZMN",
    "4T67K",
    "ODY2X",
    "WCUM",
    "demo control with \\| pipe",
    "screenshots/multiline path.png",
    "support note with \\| pipe and newline must remain on one bullet",
  ];

  for (const text of required) {
    if (!output.includes(text)) {
      throw new Error(`Expected generated report to include: ${text}`);
    }
  }

  const forbidden = [
    /\b\d{12}\b/,
    /\bA(KIA|SIA|GPA|IDA)[A-Z0-9]{16}\b/,
    /Abcdefghijklmnopqrstuvwxyz123456/,
    /support note with \\\| pipe and\nnewline/,
    /screenshots\/multiline\npath\.png/,
  ];

  for (const pattern of forbidden) {
    if (pattern.test(output)) {
      throw new Error(`Generated report leaked forbidden pattern: ${pattern}`);
    }
  }

  console.log("AWS Support evidence package check passed.");
} finally {
  fs.rmSync(tmpDir, { recursive: true, force: true });
}
