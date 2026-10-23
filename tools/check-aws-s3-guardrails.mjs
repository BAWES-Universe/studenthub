import { readFileSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const boundaryPath = resolve(
  root,
  "docs/security/aws-s3-service-user-permissions-boundary.json",
);
const alertsPath = resolve(
  root,
  "docs/security/aws-s3-bucket-change-alerts.cloudformation.json",
);

const boundaryText = readFileSync(boundaryPath, "utf8");
const alertsText = readFileSync(alertsPath, "utf8");
const boundary = JSON.parse(boundaryText);
const alertsTemplate = JSON.parse(alertsText);

const requiredDeniedActions = [
  "s3:DeleteBucketPolicy",
  "s3:PutBucketCORS",
  "s3:PutBucketLogging",
  "s3:PutBucketPolicy",
  "s3:PutBucketPublicAccessBlock",
  "s3:PutBucketVersioning",
  "s3:PutLifecycleConfiguration",
  "s3:PutReplicationConfiguration",
];

const requiredAlertEvents = [
  "DeleteBucketCors",
  "DeleteBucketPolicy",
  "PutBucketCors",
  "PutBucketLifecycle",
  "PutBucketLifecycleConfiguration",
  "PutBucketLogging",
  "PutBucketPolicy",
  "PutBucketReplication",
  "PutBucketVersioning",
  "PutPublicAccessBlock",
];

function asArray(value) {
  return Array.isArray(value) ? value : [value];
}

function assert(condition, message) {
  if (!condition) {
    throw new Error(message);
  }
}

const statements = asArray(boundary.Statement);
const denyActions = new Set(
  statements
    .filter((statement) => statement.Effect === "Deny")
    .flatMap((statement) => asArray(statement.Action)),
);
const allowStatements = statements.filter((statement) => statement.Effect === "Allow");
const allowActions = new Set(allowStatements.flatMap((statement) => asArray(statement.Action)));
const allowResources = new Set(
  allowStatements.flatMap((statement) => asArray(statement.Resource)),
);

for (const action of requiredDeniedActions) {
  assert(denyActions.has(action), `Boundary is missing required deny: ${action}`);
}

for (const action of allowActions) {
  assert(action !== "s3:*", "Boundary allow list must not include s3:*");
  assert(!action.endsWith("*"), `Boundary allow action must be explicit: ${action}`);
}

assert(
  allowResources.has("arn:aws:s3:::studenthub-uploads") &&
    allowResources.has("arn:aws:s3:::studenthub-uploads/*"),
  "Boundary must include permanent StudentHub upload bucket resources",
);
assert(
  allowResources.has("arn:aws:s3:::studenthub-public-anyone-can-upload-24hr-expiry") &&
    allowResources.has("arn:aws:s3:::studenthub-public-anyone-can-upload-24hr-expiry/*"),
  "Boundary must include temp StudentHub upload bucket resources",
);

const rule =
  alertsTemplate.Resources?.StudentHubS3BucketAdminChangeRule?.Properties?.EventPattern;
const eventNames = new Set(rule?.detail?.eventName ?? []);

for (const eventName of requiredAlertEvents) {
  assert(eventNames.has(eventName), `EventBridge rule is missing event: ${eventName}`);
}

const alertTargets =
  alertsTemplate.Resources?.StudentHubS3BucketAdminChangeRule?.Properties?.Targets ?? [];
assert(alertTargets.length > 0, "EventBridge rule must route alerts to a target");
assert(
  JSON.stringify(alertTargets).includes("AlertSnsTopicArn"),
  "EventBridge target must use the maintainer-provided SNS topic parameter",
);

const topicPolicy =
  alertsTemplate.Resources?.StudentHubS3BucketAdminAlertTopicPolicy?.Properties?.PolicyDocument;
assert(topicPolicy, "Template must grant EventBridge permission to publish to the SNS topic");
assert(
  JSON.stringify(topicPolicy).includes("events.amazonaws.com") &&
    JSON.stringify(topicPolicy).includes("sns:Publish"),
  "SNS topic policy must allow EventBridge to publish alerts",
);

const combinedTemplates = `${boundaryText}\n${alertsText}`;
assert(!/AKIA[0-9A-Z]{16}/.test(combinedTemplates), "Templates must not contain AWS access keys");
assert(
  !/[0-9]{12}\.dkr\.ecr\./.test(combinedTemplates),
  "Templates must not contain concrete AWS account IDs",
);

console.log("AWS S3 guardrail templates passed validation.");
