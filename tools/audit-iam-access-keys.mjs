#!/usr/bin/env node

import { readFileSync, writeFileSync } from "node:fs";

const DEFAULT_EXPOSED_SUFFIXES = ["DOBNJ", "55KF", "OFLT", "XW5I", "TMEZ"];
const DEFAULT_MAX_ACTIVE_AGE_DAYS = 90;
const DEFAULT_MAX_INACTIVE_AGE_DAYS = 7;
const DEFAULT_MAX_UNUSED_DAYS = 60;
const REQUIRED_TAGS = ["owner", "service", "environment"];

function usage() {
  return `Usage:
  node tools/audit-iam-access-keys.mjs --keys <iam-key-inventory.csv> [options]

Options:
  --suffixes <csv>                  Exposed key suffixes to match. Default: ${DEFAULT_EXPOSED_SUFFIXES.join(",")}
  --suffixes-file <path>            Newline or comma-separated suffix list.
  --as-of <YYYY-MM-DD>              Review date. Default: today.
  --format <markdown|csv>           Output format. Default: markdown.
  --out <path>                      Write report to a file instead of stdout.
  --max-active-age-days <number>    Rotation threshold for active keys. Default: ${DEFAULT_MAX_ACTIVE_AGE_DAYS}
  --max-inactive-age-days <number>  Deletion threshold for inactive keys. Default: ${DEFAULT_MAX_INACTIVE_AGE_DAYS}
  --max-unused-days <number>        Review threshold for unused active keys. Default: ${DEFAULT_MAX_UNUSED_DAYS}
`;
}

function parseArgs(argv) {
  const options = {
    keys: null,
    suffixes: [...DEFAULT_EXPOSED_SUFFIXES],
    asOf: new Date().toISOString().slice(0, 10),
    format: "markdown",
    out: null,
    maxActiveAgeDays: DEFAULT_MAX_ACTIVE_AGE_DAYS,
    maxInactiveAgeDays: DEFAULT_MAX_INACTIVE_AGE_DAYS,
    maxUnusedDays: DEFAULT_MAX_UNUSED_DAYS,
  };

  for (let i = 2; i < argv.length; i += 1) {
    const arg = argv[i];
    const next = argv[i + 1];
    if (arg === "--help" || arg === "-h") {
      options.help = true;
    } else if (arg === "--keys") {
      options.keys = next;
      i += 1;
    } else if (arg === "--suffixes") {
      options.suffixes = splitSuffixes(next);
      i += 1;
    } else if (arg === "--suffixes-file") {
      options.suffixes = splitSuffixes(readFileSync(next, "utf8"));
      i += 1;
    } else if (arg === "--as-of") {
      options.asOf = next;
      i += 1;
    } else if (arg === "--format") {
      options.format = next;
      i += 1;
    } else if (arg === "--out") {
      options.out = next;
      i += 1;
    } else if (arg === "--max-active-age-days") {
      options.maxActiveAgeDays = Number(next);
      i += 1;
    } else if (arg === "--max-inactive-age-days") {
      options.maxInactiveAgeDays = Number(next);
      i += 1;
    } else if (arg === "--max-unused-days") {
      options.maxUnusedDays = Number(next);
      i += 1;
    } else {
      throw new Error(`Unknown argument: ${arg}`);
    }
  }

  if (options.format !== "markdown" && options.format !== "csv") {
    throw new Error("--format must be markdown or csv");
  }

  if (!options.help && !options.keys) {
    throw new Error("--keys is required");
  }

  if (Number.isNaN(Date.parse(`${options.asOf}T00:00:00Z`))) {
    throw new Error("--as-of must be a YYYY-MM-DD date");
  }

  for (const field of ["maxActiveAgeDays", "maxInactiveAgeDays", "maxUnusedDays"]) {
    if (!Number.isFinite(options[field]) || options[field] < 0) {
      throw new Error(`--${field.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`)} must be a non-negative number`);
    }
  }

  return options;
}

function splitSuffixes(value = "") {
  return value
    .split(/[\s,]+/)
    .map((suffix) => suffix.trim().toUpperCase())
    .filter(Boolean);
}

function parseCsv(text) {
  const rows = [];
  let row = [];
  let cell = "";
  let inQuotes = false;

  for (let i = 0; i < text.length; i += 1) {
    const char = text[i];
    const next = text[i + 1];

    if (inQuotes) {
      if (char === '"' && next === '"') {
        cell += '"';
        i += 1;
      } else if (char === '"') {
        inQuotes = false;
      } else {
        cell += char;
      }
      continue;
    }

    if (char === '"') {
      inQuotes = true;
    } else if (char === ",") {
      row.push(cell);
      cell = "";
    } else if (char === "\n") {
      row.push(cell);
      rows.push(row);
      row = [];
      cell = "";
    } else if (char !== "\r") {
      cell += char;
    }
  }

  if (cell.length > 0 || row.length > 0) {
    row.push(cell);
    rows.push(row);
  }

  const [headers, ...data] = rows.filter((r) => r.some((cellValue) => cellValue.trim() !== ""));
  if (!headers) {
    return [];
  }

  return data.map((values) => {
    const output = {};
    headers.forEach((header, index) => {
      output[normalizeHeader(header)] = values[index]?.trim() ?? "";
    });
    return output;
  });
}

function normalizeHeader(header) {
  return header.trim().toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^_+|_+$/g, "");
}

function field(row, names) {
  for (const name of names) {
    const normalized = normalizeHeader(name);
    if (row[normalized] !== undefined && row[normalized] !== "") {
      return row[normalized];
    }
  }
  return "";
}

function normalizeKeyRow(row) {
  const rawKeyId = field(row, ["access_key_id", "accessKeyId", "key_id", "keyId", "id"]);
  const statusText = field(row, ["status", "active", "access_key_status"]).toLowerCase();
  const status = ["true", "yes", "active", "1"].includes(statusText)
    ? "active"
    : ["false", "no", "inactive", "0"].includes(statusText)
      ? "inactive"
      : statusText || "unknown";

  return {
    user: field(row, ["user", "user_name", "iam_user", "username"]) || "(unknown user)",
    accessKeyId: rawKeyId,
    status,
    createdAt: normalizeDate(field(row, ["created_at", "create_date", "created", "last_rotated", "access_key_last_rotated"])),
    lastUsedAt: normalizeDate(field(row, ["last_used_at", "last_used_date", "last_used", "access_key_last_used_date"])),
    lastUsedService: field(row, ["last_used_service", "service_last_used", "access_key_last_used_service"]),
    lastUsedRegion: field(row, ["last_used_region", "region_last_used", "access_key_last_used_region"]),
    owner: field(row, ["owner", "tag_owner"]),
    service: field(row, ["service", "tag_service", "app", "application"]),
    environment: field(row, ["environment", "env", "tag_environment"]),
    notes: field(row, ["notes", "note"]),
  };
}

function normalizeDate(value) {
  const trimmed = value.trim();
  if (!trimmed || ["n/a", "na", "none", "null", "no_information", "not_supported"].includes(trimmed.toLowerCase())) {
    return "";
  }

  const parsed = new Date(trimmed);
  return Number.isNaN(parsed.getTime()) ? "" : parsed.toISOString().slice(0, 10);
}

function daysBetween(start, end) {
  if (!start) {
    return null;
  }
  const startTime = Date.parse(`${start}T00:00:00Z`);
  const endTime = Date.parse(`${end}T00:00:00Z`);
  if (Number.isNaN(startTime) || Number.isNaN(endTime)) {
    return null;
  }
  return Math.max(0, Math.floor((endTime - startTime) / 86_400_000));
}

function keyLabel(accessKeyId, matchedSuffix) {
  if (matchedSuffix) {
    return `...${matchedSuffix}`;
  }
  if (!accessKeyId) {
    return "(missing key id)";
  }
  return `...${accessKeyId.slice(-4).toUpperCase()}`;
}

function findMatchedSuffix(accessKeyId, suffixes) {
  const upperKey = accessKeyId.toUpperCase();
  return suffixes.find((suffix) => upperKey.endsWith(suffix)) || "";
}

function analyzeRow(row, options) {
  const matchedSuffix = findMatchedSuffix(row.accessKeyId, options.suffixes);
  const ageDays = daysBetween(row.createdAt, options.asOf);
  const unusedDays = daysBetween(row.lastUsedAt || row.createdAt, options.asOf);
  const findings = [];
  const actions = [];

  if (!row.accessKeyId) {
    findings.push("missing key id in export");
    actions.push("re-export key inventory");
  }

  if (matchedSuffix && row.status === "active") {
    findings.push(`matches exposed suffix ${matchedSuffix}`);
    actions.push("rotate_and_deactivate");
  } else if (matchedSuffix) {
    findings.push(`inactive key matches exposed suffix ${matchedSuffix}`);
    actions.push("delete_after_evidence");
  }

  if (row.status === "inactive" && ageDays !== null && ageDays > options.maxInactiveAgeDays) {
    findings.push(`inactive for ${ageDays} days`);
    actions.push("delete_inactive_key");
  }

  if (row.status === "active" && ageDays !== null && ageDays > options.maxActiveAgeDays) {
    findings.push(`active key age ${ageDays} days`);
    actions.push("rotate_old_active_key");
  }

  if (row.status === "active" && !row.lastUsedAt && ageDays !== null && ageDays > options.maxInactiveAgeDays) {
    findings.push("active key has no last-used evidence");
    actions.push("review_or_disable_unused_key");
  } else if (row.status === "active" && unusedDays !== null && unusedDays > options.maxUnusedDays) {
    findings.push(`not used for ${unusedDays} days`);
    actions.push("review_or_disable_unused_key");
  }

  const missingTags = REQUIRED_TAGS.filter((tag) => !row[tag]);
  if (missingTags.length > 0) {
    findings.push(`missing tags: ${missingTags.join(", ")}`);
    actions.push("add_required_tags");
  }

  if (actions.length === 0) {
    actions.push("no_action");
  }

  const severity = severityFor(findings, actions);

  return {
    severity,
    user: row.user,
    key: keyLabel(row.accessKeyId, matchedSuffix),
    status: row.status,
    createdAt: row.createdAt || "unknown",
    ageDays: ageDays === null ? "unknown" : String(ageDays),
    lastUsedAt: row.lastUsedAt || "never/unknown",
    lastUsedService: row.lastUsedService || "",
    lastUsedRegion: row.lastUsedRegion || "",
    owner: row.owner || "",
    service: row.service || "",
    environment: row.environment || "",
    findings: findings.length > 0 ? findings : ["no findings"],
    actions: Array.from(new Set(actions)),
  };
}

function severityFor(findings, actions) {
  if (actions.includes("rotate_and_deactivate")) {
    return "critical";
  }
  if (actions.includes("delete_after_evidence") || actions.includes("delete_inactive_key")) {
    return "high";
  }
  if (actions.some((action) => action !== "no_action")) {
    return "medium";
  }
  return "ok";
}

function analyze(rows, options) {
  const normalizedRows = rows.map(normalizeKeyRow);
  return normalizedRows.map((row) => analyzeRow(row, options));
}

function escapeMarkdown(value) {
  return String(value).replace(/\|/g, "\\|").replace(/\n/g, " ");
}

function renderMarkdown(results, options) {
  const counts = results.reduce((acc, row) => {
    acc[row.severity] = (acc[row.severity] || 0) + 1;
    return acc;
  }, {});

  const rows = results
    .sort((a, b) => severityRank(a.severity) - severityRank(b.severity) || a.user.localeCompare(b.user))
    .map((row) => `| ${escapeMarkdown(row.severity)} | ${escapeMarkdown(row.user)} | ${escapeMarkdown(row.key)} | ${escapeMarkdown(row.status)} | ${escapeMarkdown(row.ageDays)} | ${escapeMarkdown(row.lastUsedAt)} | ${escapeMarkdown(row.findings.join("; "))} | ${escapeMarkdown(row.actions.join(", "))} |`);

  return `# IAM Access Key Review

Review date: ${options.asOf}

## Summary

- Critical: ${counts.critical || 0}
- High: ${counts.high || 0}
- Medium: ${counts.medium || 0}
- OK: ${counts.ok || 0}
- Exposed suffix watchlist: ${options.suffixes.map((suffix) => `\`...${suffix}\``).join(", ")}

## Findings

| Severity | IAM user | Key suffix | Status | Age days | Last used | Findings | Recommended action |
|---|---|---:|---|---:|---|---|---|
${rows.join("\n")}

## Evidence Package Checklist

- Store the raw IAM export in the private incident folder, not in GitHub.
- For each \`rotate_and_deactivate\` key, capture the IAM user, key suffix, replacement key creation time, deactivation time, and smoke-test result.
- For each \`delete_after_evidence\` or \`delete_inactive_key\` key, capture the deletion timestamp before closing the AWS Support evidence package.
- Add missing \`owner\`, \`service\`, and \`environment\` tags before the next monthly key review.
`;
}

function severityRank(severity) {
  return { critical: 0, high: 1, medium: 2, ok: 3 }[severity] ?? 4;
}

function csvCell(value) {
  const text = Array.isArray(value) ? value.join("; ") : String(value);
  return `"${text.replace(/"/g, '""')}"`;
}

function renderCsv(results) {
  const headers = [
    "severity",
    "user",
    "key_suffix",
    "status",
    "created_at",
    "age_days",
    "last_used_at",
    "last_used_service",
    "last_used_region",
    "owner",
    "service",
    "environment",
    "findings",
    "recommended_actions",
  ];
  const rows = results
    .sort((a, b) => severityRank(a.severity) - severityRank(b.severity) || a.user.localeCompare(b.user))
    .map((row) => [
      row.severity,
      row.user,
      row.key,
      row.status,
      row.createdAt,
      row.ageDays,
      row.lastUsedAt,
      row.lastUsedService,
      row.lastUsedRegion,
      row.owner,
      row.service,
      row.environment,
      row.findings,
      row.actions,
    ].map(csvCell).join(","));

  return `${headers.join(",")}\n${rows.join("\n")}\n`;
}

function main() {
  try {
    const options = parseArgs(process.argv);
    if (options.help) {
      process.stdout.write(usage());
      return;
    }

    const rows = parseCsv(readFileSync(options.keys, "utf8"));
    const results = analyze(rows, options);
    const report = options.format === "csv" ? renderCsv(results) : renderMarkdown(results, options);

    if (options.out) {
      writeFileSync(options.out, report);
    } else {
      process.stdout.write(report);
    }
  } catch (error) {
    process.stderr.write(`${error.message}\n\n${usage()}`);
    process.exitCode = 1;
  }
}

if (import.meta.url === `file://${process.argv[1]}`) {
  main();
}

export {
  analyze,
  parseCsv,
  renderCsv,
  renderMarkdown,
  splitSuffixes,
};
