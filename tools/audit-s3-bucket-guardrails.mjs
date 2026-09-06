#!/usr/bin/env node
import { readFileSync } from "node:fs"
import { fileURLToPath } from "node:url"

const REQUIRED_PUBLIC_ACCESS_BLOCK_FLAGS = [
  "BlockPublicAcls",
  "IgnorePublicAcls",
  "BlockPublicPolicy",
  "RestrictPublicBuckets",
]

const statusRank = {
  pass: 0,
  warn: 1,
  fail: 2,
}

function normalizeStatus(status) {
  if (!["pass", "warn", "fail"].includes(status)) {
    throw new Error(`Unknown guardrail status: ${status}`)
  }
  return status
}

function finding(id, status, title, evidence, remediation) {
  return {
    id,
    status: normalizeStatus(status),
    title,
    evidence,
    remediation,
  }
}

function getPublicAccessBlockFlags(input) {
  return input.publicAccessBlock?.PublicAccessBlockConfiguration ?? {}
}

function hasDefaultEncryption(input) {
  const rules = input.encryption?.ServerSideEncryptionConfiguration?.Rules
  return Array.isArray(rules) && rules.some((rule) => {
    const encryptionDefault = rule.ApplyServerSideEncryptionByDefault
    return Boolean(encryptionDefault?.SSEAlgorithm)
  })
}

function getObjectOwnership(input) {
  return input.ownershipControls?.OwnershipControls?.Rules?.[0]?.ObjectOwnership
}

function hasMultipartAbortLifecycle(input) {
  const rules = input.lifecycle?.Rules
  return Array.isArray(rules) && rules.some((rule) => {
    return rule.Status === "Enabled" && Boolean(rule.AbortIncompleteMultipartUpload)
  })
}

function hasWildcardWriteCors(input) {
  const rules = input.cors?.CORSRules
  if (!Array.isArray(rules)) {
    return false
  }

  return rules.some((rule) => {
    const origins = rule.AllowedOrigins ?? []
    const methods = rule.AllowedMethods ?? []
    const allowsWildcard = origins.includes("*")
    const allowsWrite = methods.some((method) => ["PUT", "POST", "DELETE"].includes(method))
    return allowsWildcard && allowsWrite
  })
}

export function auditBucketGuardrails(input) {
  const bucket = input.bucket ?? input.Bucket ?? input.name ?? ""
  const publicFlags = getPublicAccessBlockFlags(input)
  const missingPublicFlags = REQUIRED_PUBLIC_ACCESS_BLOCK_FLAGS.filter((flag) => publicFlags[flag] !== true)
  const policyIsPublic = input.policyStatus?.PolicyStatus?.IsPublic === true
  const versioningStatus = input.versioning?.Status
  const objectOwnership = getObjectOwnership(input)
  const findings = [
    finding(
      "bucket-policy-public",
      policyIsPublic ? "fail" : "pass",
      "Bucket policy must not be public",
      policyIsPublic
        ? "AWS reports PolicyStatus.IsPublic=true."
        : "AWS reports PolicyStatus.IsPublic=false or no public policy status was exported.",
      "Remove public principals from the bucket policy and keep Civil ID access behind authenticated backend flows.",
    ),
    finding(
      "public-access-block",
      missingPublicFlags.length === 0 ? "pass" : "fail",
      "All S3 Block Public Access flags should be enabled",
      missingPublicFlags.length === 0
        ? "All four public-access-block flags are true."
        : `Missing or disabled flags: ${missingPublicFlags.join(", ")}.`,
      "Enable BlockPublicAcls, IgnorePublicAcls, BlockPublicPolicy, and RestrictPublicBuckets at bucket level.",
    ),
    finding(
      "default-encryption",
      hasDefaultEncryption(input) ? "pass" : "fail",
      "Server-side encryption should be configured",
      hasDefaultEncryption(input)
        ? "At least one server-side encryption rule is configured."
        : "Server-side encryption is not configured in the exported evidence.",
      "Enable default SSE-S3 or SSE-KMS encryption for all Civil ID objects.",
    ),
    finding(
      "versioning",
      versioningStatus === "Enabled" ? "pass" : "warn",
      "Versioning should be enabled for recovery evidence",
      versioningStatus === "Enabled"
        ? "Bucket versioning is enabled."
        : `Bucket versioning is ${versioningStatus || "not configured"}.`,
      "Enable versioning unless the maintainer intentionally accepts lower recovery coverage for this bucket.",
    ),
    finding(
      "object-ownership",
      objectOwnership === "BucketOwnerEnforced" ? "pass" : "fail",
      "Bucket owner enforced object ownership should be enabled",
      objectOwnership === "BucketOwnerEnforced"
        ? "ObjectOwnership is BucketOwnerEnforced."
        : `ObjectOwnership is ${objectOwnership || "not configured"}.`,
      "Use BucketOwnerEnforced ownership so ACLs cannot create cross-account object access drift.",
    ),
    finding(
      "multipart-lifecycle",
      hasMultipartAbortLifecycle(input) ? "pass" : "fail",
      "Incomplete multipart uploads should be cleaned up",
      hasMultipartAbortLifecycle(input)
        ? "An enabled lifecycle rule aborts incomplete multipart uploads."
        : "No enabled AbortIncompleteMultipartUpload lifecycle rule was exported.",
      "Add a lifecycle rule to abort incomplete uploads after a short retention window.",
    ),
    finding(
      "cors-wildcard-write",
      hasWildcardWriteCors(input) ? "fail" : "pass",
      "CORS must not allow wildcard write access",
      hasWildcardWriteCors(input)
        ? "At least one CORS rule allows wildcard origins with write methods."
        : "No wildcard-origin CORS rule allows PUT, POST, or DELETE.",
      "Restrict CORS origins to trusted StudentHub domains and avoid wildcard write methods.",
    ),
    finding(
      "bucket-name-present",
      bucket ? "pass" : "fail",
      "Bucket name is present in the evidence package",
      bucket ? `Auditing bucket ${bucket}.` : "No bucket name was provided.",
      "Include the S3 bucket name so remediation evidence can be traced to the right Civil ID upload bucket.",
    ),
  ]

  const summary = findings.reduce((acc, item) => {
    acc[item.status] += 1
    return acc
  }, { pass: 0, warn: 0, fail: 0 })

  const overallStatus = findings.reduce((status, item) => {
    return statusRank[item.status] > statusRank[status] ? item.status : status
  }, "pass")

  return {
    bucket,
    overallStatus,
    summary,
    findings,
  }
}

export function formatMarkdownReport(report) {
  const lines = [
    `# S3 Bucket Guardrail Audit: ${report.bucket || "unknown bucket"}`,
    "",
    `Overall status: ${report.overallStatus.toUpperCase()}`,
    "",
    `Summary: ${report.summary.pass} pass, ${report.summary.warn} warning, ${report.summary.fail} fail`,
    "",
    "| Status | Guardrail | Evidence | Remediation |",
    "| --- | --- | --- | --- |",
  ]

  for (const item of report.findings) {
    lines.push(`| ${item.status.toUpperCase()} | ${escapeTable(item.title)} | ${escapeTable(item.evidence)} | ${escapeTable(item.remediation)} |`)
  }

  lines.push("")
  lines.push("This report intentionally summarizes posture evidence without printing raw bucket policies or secrets.")
  return `${lines.join("\n")}\n`
}

function escapeTable(value) {
  return String(value).replaceAll("|", "\\|").replaceAll("\n", " ")
}

function parseArgs(argv) {
  const args = {
    input: undefined,
    format: "markdown",
  }

  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i]
    if (arg === "--input") {
      args.input = argv[++i]
    } else if (arg === "--format") {
      args.format = argv[++i]
    } else if (arg === "--help" || arg === "-h") {
      args.help = true
    } else {
      throw new Error(`Unknown argument: ${arg}`)
    }
  }

  return args
}

function usage() {
  return [
    "Usage: node tools/audit-s3-bucket-guardrails.mjs --input <evidence.json> [--format markdown|json]",
    "",
    "The evidence JSON can contain AWS CLI outputs from:",
    "- s3api get-public-access-block",
    "- s3api get-bucket-policy-status",
    "- s3api get-bucket-encryption",
    "- s3api get-bucket-versioning",
    "- s3api get-bucket-ownership-controls",
    "- s3api get-bucket-lifecycle-configuration",
    "- s3api get-bucket-cors",
  ].join("\n")
}

function main() {
  const args = parseArgs(process.argv.slice(2))
  if (args.help) {
    console.log(usage())
    return
  }
  if (!args.input) {
    throw new Error("Missing --input")
  }

  const evidence = JSON.parse(readFileSync(args.input, "utf8"))
  const report = auditBucketGuardrails(evidence)

  if (args.format === "json") {
    console.log(JSON.stringify(report, null, 2))
    return
  }
  if (args.format === "markdown") {
    process.stdout.write(formatMarkdownReport(report))
    return
  }

  throw new Error(`Unsupported --format: ${args.format}`)
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  try {
    main()
  } catch (error) {
    console.error(error.message)
    console.error("")
    console.error(usage())
    process.exitCode = 1
  }
}
