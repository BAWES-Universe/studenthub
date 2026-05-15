#!/usr/bin/env node

import fs from "node:fs";
import path from "node:path";

const DEFAULT_BUCKET_PREFIXES = ["studenthub-"];
const DEFAULT_USERS = ["railway-s3-access", "n8n-s3-access", "mediaconverter"];

const BUCKET_ADMIN_EVENTS = new Set([
  "DeleteBucketCors",
  "DeleteBucketPolicy",
  "DeleteBucketReplication",
  "DeleteBucketWebsite",
  "PutBucketAcl",
  "PutBucketCors",
  "PutBucketLifecycleConfiguration",
  "PutBucketLogging",
  "PutBucketOwnershipControls",
  "PutBucketPolicy",
  "PutBucketPublicAccessBlock",
  "PutBucketReplication",
  "PutBucketVersioning",
  "PutBucketWebsite",
  "DeletePublicAccessBlock",
  "PutPublicAccessBlock",
]);

function printUsage() {
  console.log(`Usage:
  node tools/audit-cloudtrail-s3-admin-events.mjs <cloudtrail-json-file-or-dir> [more files]

Options:
  --format markdown|csv       Output format. Defaults to markdown.
  --out <path>                Write output to a file instead of stdout.
  --bucket-prefix <prefix>    Match buckets by prefix. Repeat or comma-separate.
                              Defaults to studenthub-.
  --all-buckets               Do not filter by bucket prefix.
  --user <name>               Match IAM user names. Repeat or comma-separate.
                              Defaults to railway-s3-access,n8n-s3-access,mediaconverter.
  --all-users                 Do not filter by IAM user.
`);
}

function parseArgs(argv) {
  const options = {
    format: "markdown",
    out: null,
    bucketPrefixes: [...DEFAULT_BUCKET_PREFIXES],
    users: [...DEFAULT_USERS],
    inputs: [],
  };

  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i];
    const readValue = () => {
      const value = arg.includes("=") ? arg.slice(arg.indexOf("=") + 1) : argv[++i];
      if (!value) throw new Error(`Missing value for ${arg}`);
      return value;
    };

    if (arg === "--help" || arg === "-h") {
      printUsage();
      process.exit(0);
    } else if (arg === "--format" || arg.startsWith("--format=")) {
      options.format = readValue();
    } else if (arg === "--out" || arg.startsWith("--out=")) {
      options.out = readValue();
    } else if (arg === "--bucket-prefix" || arg.startsWith("--bucket-prefix=")) {
      options.bucketPrefixes.push(...readValue().split(",").map((value) => value.trim()).filter(Boolean));
    } else if (arg === "--all-buckets") {
      options.bucketPrefixes = [];
    } else if (arg === "--user" || arg.startsWith("--user=")) {
      options.users.push(...readValue().split(",").map((value) => value.trim()).filter(Boolean));
    } else if (arg === "--all-users") {
      options.users = [];
    } else if (arg.startsWith("-")) {
      throw new Error(`Unknown option: ${arg}`);
    } else {
      options.inputs.push(arg);
    }
  }

  options.format = options.format.toLowerCase();
  if (!["markdown", "csv"].includes(options.format)) {
    throw new Error("--format must be markdown or csv");
  }

  options.bucketPrefixes = unique(options.bucketPrefixes);
  options.users = unique(options.users);

  return options;
}

function unique(values) {
  return [...new Set(values)];
}

function collectJsonFiles(inputPath) {
  const resolvedPath = path.resolve(inputPath);
  const stat = fs.statSync(resolvedPath);

  if (stat.isDirectory()) {
    return fs
      .readdirSync(resolvedPath, { withFileTypes: true })
      .flatMap((entry) => collectJsonFiles(path.join(resolvedPath, entry.name)))
      .filter((filePath) => filePath.endsWith(".json"));
  }

  return [resolvedPath];
}

function readRecords(filePath) {
  let parsed;
  try {
    parsed = JSON.parse(fs.readFileSync(filePath, "utf8"));
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    throw new Error(`Failed to parse JSON in ${filePath}: ${message}`, { cause: error });
  }

  const records = Array.isArray(parsed)
    ? parsed
    : Array.isArray(parsed.Records)
      ? parsed.Records
      : Array.isArray(parsed.events)
        ? parsed.events
        : [parsed];

  return records.map((record) => normalizeRecord(record, filePath));
}

function normalizeRecord(record, filePath) {
  if (typeof record.CloudTrailEvent === "string") {
    return {
      ...JSON.parse(record.CloudTrailEvent),
      _sourceFile: filePath,
    };
  }

  return {
    ...record,
    _sourceFile: filePath,
  };
}

function bucketFromRecord(record) {
  const request = record.requestParameters ?? {};
  const directBucket = request.bucketName ?? request.bucket ?? request.Bucket;
  if (directBucket) return String(directBucket);

  for (const resource of record.resources ?? []) {
    const resourceName = resource.resourceName ?? resource.ARN ?? resource.arn ?? "";
    const bucketMatch = String(resourceName).match(/^arn:aws:s3:::(?<bucket>[^/]+)/);
    if (bucketMatch?.groups?.bucket) return bucketMatch.groups.bucket;
  }

  return "";
}

function userNameFromRecord(record) {
  const identity = record.userIdentity ?? {};
  return (
    identity.userName ??
    identity.sessionContext?.sessionIssuer?.userName ??
    identity.principalId ??
    ""
  );
}

function accessKeySuffix(record) {
  const accessKeyId = record.userIdentity?.accessKeyId;
  if (!accessKeyId) return "";
  return String(accessKeyId).slice(-4);
}

function eventMatches(record, options) {
  if (!BUCKET_ADMIN_EVENTS.has(record.eventName)) return false;

  const bucketName = bucketFromRecord(record);
  if (
    options.bucketPrefixes.length > 0 &&
    !options.bucketPrefixes.some((prefix) => bucketName.startsWith(prefix))
  ) {
    return false;
  }

  const userName = userNameFromRecord(record);
  if (options.users.length > 0 && !options.users.includes(userName)) {
    return false;
  }

  return true;
}

function buildEvent(record) {
  return {
    eventTime: record.eventTime ?? "",
    eventName: record.eventName ?? "",
    userName: userNameFromRecord(record),
    accessKeyIdSuffix: accessKeySuffix(record),
    sourceIPAddress: record.sourceIPAddress ?? "",
    userAgent: record.userAgent ?? "",
    bucketName: bucketFromRecord(record),
    awsRegion: record.awsRegion ?? "",
    errorCode: record.errorCode ?? "",
    sourceFile: record._sourceFile ?? "",
  };
}

function compareEvents(left, right) {
  return (
    String(left.eventTime).localeCompare(String(right.eventTime)) ||
    String(left.eventName).localeCompare(String(right.eventName))
  );
}

function groupBy(events, key) {
  return events.reduce((groups, event) => {
    const value = event[key] || "(empty)";
    groups.set(value, [...(groups.get(value) ?? []), event]);
    return groups;
  }, new Map());
}

function renderMarkdown(events, options, files) {
  const lines = [
    "# CloudTrail S3 Bucket Admin Audit",
    "",
    `- Input files: ${files.length}`,
    `- Matching events: ${events.length}`,
    `- Bucket filter: ${options.bucketPrefixes.length ? options.bucketPrefixes.join(", ") : "all buckets"}`,
    `- User filter: ${options.users.length ? options.users.join(", ") : "all users"}`,
    "",
  ];

  if (events.length === 0) {
    lines.push("No matching S3 bucket-admin events were found.");
    return lines.join("\n");
  }

  lines.push("## Events By API", "", "| Event | Count | First Seen | Last Seen |", "|---|---:|---|---|");
  for (const [eventName, rows] of groupBy(events, "eventName")) {
    lines.push(`| ${escapeMarkdown(eventName)} | ${rows.length} | ${escapeMarkdown(rows[0].eventTime)} | ${escapeMarkdown(rows.at(-1).eventTime)} |`);
  }

  lines.push("", "## Events By User", "", "| User | Count | Key Suffixes | Event Types |", "|---|---:|---|---|");
  for (const [userName, rows] of groupBy(events, "userName")) {
    lines.push(
      `| ${escapeMarkdown(userName)} | ${rows.length} | ${escapeMarkdown(unique(rows.map((row) => row.accessKeyIdSuffix).filter(Boolean)).join(", "))} | ${escapeMarkdown(unique(rows.map((row) => row.eventName)).join(", "))} |`,
    );
  }

  lines.push(
    "",
    "## Matching Events",
    "",
    "| Time | Event | User | Key Suffix | Source IP | User Agent | Bucket | Region | Error |",
    "|---|---|---|---|---|---|---|---|---|",
  );

  for (const event of events) {
    lines.push(
      `| ${escapeMarkdown(event.eventTime)} | ${escapeMarkdown(event.eventName)} | ${escapeMarkdown(event.userName)} | ${escapeMarkdown(event.accessKeyIdSuffix)} | ${escapeMarkdown(event.sourceIPAddress)} | ${escapeMarkdown(event.userAgent)} | ${escapeMarkdown(event.bucketName)} | ${escapeMarkdown(event.awsRegion)} | ${escapeMarkdown(event.errorCode)} |`,
    );
  }

  return lines.join("\n");
}

function renderCsv(events) {
  const columns = [
    "eventTime",
    "eventName",
    "userName",
    "accessKeyIdSuffix",
    "sourceIPAddress",
    "userAgent",
    "bucketName",
    "awsRegion",
    "errorCode",
    "sourceFile",
  ];

  return [
    columns.join(","),
    ...events.map((event) => columns.map((column) => quoteCsv(event[column])).join(",")),
  ].join("\n");
}

function escapeMarkdown(value) {
  return String(value ?? "").replaceAll("|", "\\|").replace(/\s+/g, " ").trim();
}

function quoteCsv(value) {
  return `"${String(value ?? "").replaceAll('"', '""')}"`;
}

function main() {
  const options = parseArgs(process.argv.slice(2));
  if (options.inputs.length === 0) {
    printUsage();
    process.exitCode = 1;
    return;
  }

  const files = unique(options.inputs.flatMap(collectJsonFiles));
  const events = files
    .flatMap(readRecords)
    .filter((record) => eventMatches(record, options))
    .map(buildEvent)
    .sort(compareEvents);

  const output = options.format === "csv"
    ? renderCsv(events)
    : renderMarkdown(events, options, files);

  if (options.out) {
    fs.mkdirSync(path.dirname(path.resolve(options.out)), { recursive: true });
    fs.writeFileSync(options.out, `${output}\n`);
  } else {
    console.log(output);
  }
}

try {
  main();
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
