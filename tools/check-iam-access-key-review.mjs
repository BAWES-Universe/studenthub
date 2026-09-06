#!/usr/bin/env node

import { execFileSync } from "node:child_process";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const root = dirname(dirname(fileURLToPath(import.meta.url)));
const script = join(root, "tools", "audit-iam-access-keys.mjs");
const fixture = join(root, "tools", "fixtures", "iam-access-keys.sample.csv");

function run(args) {
  return execFileSync(process.execPath, [script, "--keys", fixture, "--as-of", "2026-05-15", ...args], {
    cwd: root,
    encoding: "utf8",
  });
}

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

const markdown = run([]);
assert(markdown.includes("rotate_and_deactivate"), "expected active exposed key rotation action");
assert(markdown.includes("delete_after_evidence"), "expected inactive exposed key evidence action");
assert(markdown.includes("add_required_tags"), "expected missing tag action");
assert(markdown.includes("...DOBNJ"), "expected redacted exposed key suffix");
assert(!markdown.includes("studenthub-temp-key-DOBNJ"), "markdown leaked full key id");
assert(!markdown.includes("studenthub-old-key-XW5I"), "markdown leaked full inactive key id");

const csv = run(["--format", "csv"]);
assert(csv.startsWith("severity,user,key_suffix"), "expected CSV header");
assert(csv.includes('"critical"'), "expected critical CSV row");
assert(csv.includes('"high"'), "expected high CSV row");
assert(!csv.includes("studenthub-temp-key-DOBNJ"), "CSV leaked full key id");
assert(!csv.includes("studenthub-old-key-XW5I"), "CSV leaked full inactive key id");

console.log("IAM access key review check passed");
