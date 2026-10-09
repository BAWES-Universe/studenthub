#!/usr/bin/env node

import fs from "node:fs";
import path from "node:path";

const DEFAULT_BUCKET_PATTERNS = [
  "studenthub",
  "studenthub-uploads",
  "studenthub-public",
  "plugn",
  "wallet",
];

const WRITE_METHODS = new Set(["PUT", "POST", "DELETE"]);
const REQUIRED_TAGS = ["owner", "service", "environment"];

function parseArgs(argv) {
  const options = {
    format: "markdown",
    includeAll: false,
    output: null,
    bucketPatterns: [...DEFAULT_BUCKET_PATTERNS],
  };
  const files = [];

  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];
    const readOptionValue = (name) => {
      if (index + 1 >= argv.length) {
        throw new Error(`${name} requires a value`);
      }
      index += 1;
      return argv[index];
    };

    if (arg === "--format") {
      options.format = readOptionValue("--format");
    } else if (arg === "--output") {
      options.output = readOptionValue("--output");
    } else if (arg === "--include-all") {
      options.includeAll = true;
    } else if (arg === "--bucket-pattern") {
      options.bucketPatterns.push(readOptionValue("--bucket-pattern"));
    } else if (arg === "--help" || arg === "-h") {
      printHelp();
      process.exit(0);
    } else if (arg.startsWith("-")) {
      throw new Error(`Unknown option: ${arg}`);
    } else {
      files.push(arg);
    }
  }

  if (!["markdown", "csv"].includes(options.format)) {
    throw new Error("--format must be markdown or csv");
  }
  if (files.length === 0) {
    throw new Error("Provide at least one JSON file or directory to audit");
  }

  return { files, options };
}

function printHelp() {
  console.log(`Usage: node tools/audit-s3-bucket-posture.mjs <export.json|dir> [options]

Options:
  --format markdown|csv     Output format. Default: markdown
  --output <path>           Write report to a file
  --include-all             Audit buckets even when their names do not match defaults
  --bucket-pattern <text>   Include bucket names containing this text. Can repeat
  -h, --help                Show this help

Input can be a normalized array of bucket posture objects, an object with a
"buckets" field, or an AWS-style object with "Buckets".`);
}

function listJsonFiles(target) {
  const stats = fs.statSync(target);
  if (stats.isFile()) {
    return [target];
  }
  if (!stats.isDirectory()) {
    throw new Error(`Input path is neither a file nor directory: ${target}`);
  }

  return fs
    .readdirSync(target)
    .flatMap((entry) => {
      const child = path.join(target, entry);
      const childStats = fs.statSync(child);
      if (childStats.isDirectory()) {
        return listJsonFiles(child);
      }
      return child.endsWith(".json") ? [child] : [];
    })
    .sort();
}

function parseJson(filePath) {
  try {
    return JSON.parse(fs.readFileSync(filePath, "utf8"));
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    throw new Error(`Failed to parse JSON in ${filePath}: ${message}`, { cause: error });
  }
}

function readBuckets(files) {
  return files
    .flatMap(listJsonFiles)
    .flatMap((filePath) => {
      const parsed = parseJson(filePath);
      const records = Array.isArray(parsed)
        ? parsed
        : Array.isArray(parsed.buckets)
          ? parsed.buckets
          : Array.isArray(parsed.Buckets)
            ? parsed.Buckets
            : [parsed];

      return records.map((record) => ({ ...record, sourceFile: filePath }));
    })
    .filter((record) => bucketName(record));
}

function bucketName(bucket) {
  return bucket.name ?? bucket.Name ?? bucket.Bucket ?? bucket.bucketName ?? bucket.bucket;
}

function normalizeTagMap(tags) {
  if (!tags) {
    return {};
  }
  const validTagEntries = (tagList) =>
    tagList.flatMap((tag) => {
      const key = tag?.Key ?? tag?.key;
      const value = tag?.Value ?? tag?.value;
      if (key == null || value == null) {
        return [];
      }
      return [[String(key), String(value)]];
    });

  if (Array.isArray(tags)) {
    return Object.fromEntries(validTagEntries(tags));
  }
  if (Array.isArray(tags.TagSet)) {
    return Object.fromEntries(validTagEntries(tags.TagSet));
  }
  return Object.fromEntries(Object.entries(tags).map(([key, value]) => [key, String(value)]));
}

function getVersioningStatus(bucket) {
  const versioning = bucket.versioning ?? bucket.VersioningConfiguration ?? bucket.Versioning;
  if (typeof versioning === "string") {
    return versioning;
  }
  return versioning?.Status ?? versioning?.status ?? "Disabled";
}

function getLifecycleRules(bucket) {
  const lifecycle = bucket.lifecycle ?? bucket.LifecycleConfiguration ?? bucket.Lifecycle;
  const rules = lifecycle?.Rules ?? lifecycle?.rules ?? lifecycle;
  return Array.isArray(rules) ? rules : [];
}

function getCorsRules(bucket) {
  const cors = bucket.cors ?? bucket.CORSConfiguration ?? bucket.CORS;
  const rules = cors?.CORSRules ?? cors?.rules ?? cors;
  return Array.isArray(rules) ? rules : [];
}

function getPublicAccessBlock(bucket) {
  return (
    bucket.publicAccessBlock ??
    bucket.PublicAccessBlockConfiguration ??
    bucket.PublicAccessBlock ??
    null
  );
}

function getOwnershipMode(bucket) {
  const ownership = bucket.ownershipControls ?? bucket.OwnershipControls ?? bucket.Ownership;
  const rule = ownership?.Rules?.[0] ?? ownership?.rules?.[0] ?? ownership;
  return rule?.ObjectOwnership ?? rule?.objectOwnership ?? bucket.objectOwnership ?? null;
}

function hasLogging(bucket) {
  const logging = bucket.logging ?? bucket.BucketLoggingStatus ?? bucket.Logging;
  return Boolean(logging?.LoggingEnabled ?? logging?.loggingEnabled ?? logging?.targetBucket);
}

function isTargetBucket(bucket, options) {
  const name = bucketName(bucket).toLowerCase();
  return options.includeAll || options.bucketPatterns.some((pattern) => name.includes(pattern.toLowerCase()));
}

function isTemporaryBucket(bucket) {
  const name = bucketName(bucket).toLowerCase();
  return (
    name.includes("temp") ||
    name.includes("24hr") ||
    name.includes("public-anyone-can-upload") ||
    normalizeTagMap(bucket.tags ?? bucket.Tags).environment === "temporary"
  );
}

function addFinding(findings, bucket, severity, check, finding, remediation) {
  findings.push({
    bucket: bucketName(bucket),
    severity,
    check,
    finding,
    remediation,
    sourceFile: bucket.sourceFile,
  });
}

function auditBucket(bucket) {
  const findings = [];
  const name = bucketName(bucket);
  const tempBucket = isTemporaryBucket(bucket);
  const tags = normalizeTagMap(bucket.tags ?? bucket.Tags);

  if (getVersioningStatus(bucket) !== "Enabled" && !tempBucket) {
    addFinding(
      findings,
      bucket,
      "high",
      "versioning",
      "Versioning is not enabled for a permanent bucket.",
      "Enable S3 versioning before further remediation so accidental or automated deletions can be recovered.",
    );
  }

  const lifecycleRules = getLifecycleRules(bucket).filter((rule) => (rule.Status ?? rule.status) === "Enabled");
  for (const rule of lifecycleRules) {
    const ruleName = rule.ID ?? rule.id ?? "(unnamed)";
    const expirations = [
      {
        days: Number(rule.Expiration?.Days ?? rule.expiration?.days),
        target: "current objects",
      },
      {
        days: Number(
          rule.NoncurrentVersionExpiration?.NoncurrentDays ??
            rule.NoncurrentVersionExpiration?.noncurrentDays ??
            rule.noncurrentVersionExpiration?.NoncurrentDays ??
            rule.noncurrentVersionExpiration?.noncurrentDays,
        ),
        target: "noncurrent versions",
      },
    ].filter((expiration) => Number.isFinite(expiration.days));

    for (const expiration of expirations) {
      if (!tempBucket && expiration.days <= 30) {
        addFinding(
          findings,
          bucket,
          "critical",
          "lifecycle",
          `Enabled lifecycle rule ${ruleName} can expire ${expiration.target} after ${expiration.days} days.`,
          "Disable destructive lifecycle rules on permanent buckets or scope them to explicitly temporary prefixes.",
        );
      } else if (tempBucket && expiration.days > 7) {
        addFinding(
          findings,
          bucket,
          "medium",
          "lifecycle",
          `Temporary bucket lifecycle rule ${ruleName} expires ${expiration.target} after ${expiration.days} days.`,
          "Confirm the temp bucket retention window is intentional and short enough for public upload staging.",
        );
      }
    }
  }

  const publicAccess = getPublicAccessBlock(bucket);
  if (!publicAccess) {
    addFinding(
      findings,
      bucket,
      "high",
      "public access",
      "Public access block settings were not included in the export.",
      "Export and verify BlockPublicAcls, IgnorePublicAcls, BlockPublicPolicy, and RestrictPublicBuckets are all true.",
    );
  } else {
    for (const key of ["BlockPublicAcls", "IgnorePublicAcls", "BlockPublicPolicy", "RestrictPublicBuckets"]) {
      if (publicAccess[key] !== true && publicAccess[key[0].toLowerCase() + key.slice(1)] !== true) {
        addFinding(
          findings,
          bucket,
          "high",
          "public access",
          `${key} is not enabled.`,
          "Enable all S3 public access block flags unless a documented exception exists.",
        );
      }
    }
  }

  for (const rule of getCorsRules(bucket)) {
    const origins = rule.AllowedOrigins ?? rule.allowedOrigins ?? [];
    const methods = rule.AllowedMethods ?? rule.allowedMethods ?? [];
    const hasWildcardOrigin = origins.includes("*");
    const hasWriteMethod = methods.some((method) => WRITE_METHODS.has(String(method).toUpperCase()));
    if (hasWildcardOrigin && hasWriteMethod) {
      addFinding(
        findings,
        bucket,
        "high",
        "cors",
        "CORS allows wildcard origins for write methods.",
        "Restrict write-capable CORS rules to the expected StudentHub frontend origins.",
      );
    }
  }

  const acl = bucket.acl ?? bucket.ACL ?? bucket.AccessControlPolicy;
  const grants = acl?.Grants ?? acl?.grants ?? [];
  for (const grant of grants) {
    const uri = grant.Grantee?.URI ?? grant.grantee?.uri ?? "";
    if (uri.includes("AllUsers") || uri.includes("AuthenticatedUsers")) {
      addFinding(
        findings,
        bucket,
        "high",
        "acl",
        "Bucket ACL grants access to a public AWS group.",
        "Remove public ACL grants and rely on bucket-owner-enforced object ownership.",
      );
    }
  }

  const ownershipMode = getOwnershipMode(bucket);
  if (ownershipMode !== "BucketOwnerEnforced") {
    addFinding(
      findings,
      bucket,
      "medium",
      "ownership",
      `Object ownership is ${ownershipMode ?? "not exported"}.`,
      "Set object ownership to BucketOwnerEnforced unless there is a documented ACL compatibility exception.",
    );
  }

  if (!hasLogging(bucket)) {
    addFinding(
      findings,
      bucket,
      "medium",
      "logging",
      "Server access logging was not exported as enabled.",
      "Enable bucket access logging or document why CloudTrail data events provide equivalent coverage.",
    );
  }

  const missingTags = REQUIRED_TAGS.filter((tag) => !tags[tag]);
  if (missingTags.length > 0) {
    addFinding(
      findings,
      bucket,
      "medium",
      "tags",
      `Missing ownership tags: ${missingTags.join(", ")}.`,
      "Tag every bucket with owner, service, and environment for IAM review and incident response.",
    );
  }

  if (findings.length === 0) {
    addFinding(
      findings,
      bucket,
      "info",
      "posture",
      `${name} has no findings in the exported posture data.`,
      "Keep this export with the AWS Support evidence package.",
    );
  }

  return findings;
}

function severityRank(severity) {
  return { critical: 0, high: 1, medium: 2, low: 3, info: 4 }[severity] ?? 5;
}

function audit(files, options) {
  return readBuckets(files)
    .filter((bucket) => isTargetBucket(bucket, options))
    .flatMap(auditBucket)
    .sort((left, right) => {
      const severityDiff = severityRank(left.severity) - severityRank(right.severity);
      if (severityDiff !== 0) return severityDiff;
      return left.bucket.localeCompare(right.bucket) || left.check.localeCompare(right.check);
    });
}

function redact(value) {
  return String(value)
    .replace(/\b\d{12}\b/g, "************")
    .replace(/\b(A3T|AKIA|ASIA)[A-Z0-9]{16}\b/g, "$1****************");
}

function renderMarkdown(findings) {
  const rows = findings.map((finding) => [
    finding.severity,
    finding.bucket,
    finding.check,
    finding.finding,
    finding.remediation,
  ]);
  return [
    "# S3 Bucket Posture Audit",
    "",
    `Findings: ${findings.length}`,
    "",
    "| Severity | Bucket | Check | Finding | Remediation |",
    "|---|---|---|---|---|",
    ...rows.map((row) => `| ${row.map((cell) => redact(cell).replaceAll("|", "\\|")).join(" | ")} |`),
    "",
  ].join("\n");
}

function csvCell(value) {
  const escaped = redact(value).replaceAll('"', '""');
  return `"${escaped}"`;
}

function renderCsv(findings) {
  const header = ["severity", "bucket", "check", "finding", "remediation", "source_file"];
  const rows = findings.map((finding) => [
    finding.severity,
    finding.bucket,
    finding.check,
    finding.finding,
    finding.remediation,
    finding.sourceFile,
  ]);
  return [header, ...rows].map((row) => row.map(csvCell).join(",")).join("\n") + "\n";
}

function main() {
  const { files, options } = parseArgs(process.argv.slice(2));
  const findings = audit(files, options);
  const report = options.format === "csv" ? renderCsv(findings) : renderMarkdown(findings);

  if (options.output) {
    fs.writeFileSync(options.output, report);
  } else {
    process.stdout.write(report);
  }

  if (findings.some((finding) => ["critical", "high"].includes(finding.severity))) {
    process.exitCode = 2;
  }
}

try {
  main();
} catch (error) {
  console.error(error instanceof Error ? error.message : String(error));
  process.exit(1);
}
