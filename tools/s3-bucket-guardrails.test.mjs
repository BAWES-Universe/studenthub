import assert from "node:assert/strict"
import { execFileSync } from "node:child_process"
import { dirname, join } from "node:path"
import { describe, it } from "node:test"
import { fileURLToPath } from "node:url"

import { auditBucketGuardrails, formatMarkdownReport } from "./audit-s3-bucket-guardrails.mjs"

const toolsDir = dirname(fileURLToPath(import.meta.url))

describe("auditBucketGuardrails", () => {
  it("flags unsafe bucket posture without leaking raw policy contents", () => {
    const report = auditBucketGuardrails({
      bucket: "studenthub-civil-id-uploads",
      publicAccessBlock: {
        PublicAccessBlockConfiguration: {
          BlockPublicAcls: true,
          IgnorePublicAcls: false,
          BlockPublicPolicy: true,
          RestrictPublicBuckets: false,
        },
      },
      policyStatus: { PolicyStatus: { IsPublic: true } },
      encryption: {},
      versioning: { Status: "Suspended" },
      ownershipControls: {
        OwnershipControls: {
          Rules: [{ ObjectOwnership: "ObjectWriter" }],
        },
      },
      lifecycle: {},
      cors: {
        CORSRules: [
          {
            AllowedOrigins: ["*"],
            AllowedMethods: ["GET", "PUT", "POST"],
          },
        ],
      },
    })

    assert.equal(report.summary.fail, 6)
    assert.equal(report.summary.warn, 1)
    assert.equal(report.summary.pass, 1)
    assert.equal(report.findings[0].id, "bucket-policy-public")

    const markdown = formatMarkdownReport(report)
    assert.match(markdown, /studenthub-civil-id-uploads/)
    assert.match(markdown, /Server-side encryption is not configured/)
    assert.doesNotMatch(markdown, /Statement/)
    assert.doesNotMatch(markdown, /Principal/)
  })

  it("passes the expected private Civil ID upload bucket guardrails", () => {
    const report = auditBucketGuardrails({
      bucket: "studenthub-civil-id-uploads",
      publicAccessBlock: {
        PublicAccessBlockConfiguration: {
          BlockPublicAcls: true,
          IgnorePublicAcls: true,
          BlockPublicPolicy: true,
          RestrictPublicBuckets: true,
        },
      },
      policyStatus: { PolicyStatus: { IsPublic: false } },
      encryption: {
        ServerSideEncryptionConfiguration: {
          Rules: [
            {
              ApplyServerSideEncryptionByDefault: {
                SSEAlgorithm: "AES256",
              },
            },
          ],
        },
      },
      versioning: { Status: "Enabled" },
      ownershipControls: {
        OwnershipControls: {
          Rules: [{ ObjectOwnership: "BucketOwnerEnforced" }],
        },
      },
      lifecycle: {
        Rules: [
          {
            Status: "Enabled",
            AbortIncompleteMultipartUpload: {
              DaysAfterInitiation: 7,
            },
          },
        ],
      },
      cors: {
        CORSRules: [
          {
            AllowedOrigins: ["https://staff.example.com"],
            AllowedMethods: ["GET", "PUT"],
          },
        ],
      },
    })

    assert.deepEqual(report.summary, { pass: 8, warn: 0, fail: 0 })
  })

  it("prints a markdown report when executed as a CLI", () => {
    const output = execFileSync(process.execPath, [
      join(toolsDir, "audit-s3-bucket-guardrails.mjs"),
      "--input",
      join(toolsDir, "fixtures", "s3-bucket-guardrails.sample.json"),
    ], { encoding: "utf8" })

    assert.match(output, /S3 Bucket Guardrail Audit: studenthub-civil-id-uploads/)
    assert.match(output, /Overall status: PASS/)
  })
})
