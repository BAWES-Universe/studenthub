#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Offline Civil ID file recovery planner.
 *
 * This script intentionally does not connect to the database, S3, AWS APIs, or
 * application runtime. Feed it exported CSV/key-list files and it writes a
 * deterministic plan for which Civil ID files are present, recoverable from a
 * legacy/temp prefix, or require candidate re-upload.
 */

const EXIT_USAGE = 2;

main($argv);

function main(array $argv): void
{
    $options = parseOptions($argv);

    if (isset($options['help'])) {
        printUsage();
        exit(0);
    }

    $candidateCsv = requireOption($options, 'candidate-csv');
    $outputPath = $options['output'] ?? null;
    $format = strtolower($options['format'] ?? 'json');

    if (!in_array($format, ['json', 'csv'], true)) {
        fwrite(STDERR, "Invalid --format. Expected json or csv.\n");
        exit(EXIT_USAGE);
    }

    $permanentKeys = readKeySet($options['permanent-keys'] ?? null);
    $legacyKeys = readKeySet($options['legacy-keys'] ?? null);
    $tempKeys = readKeySet($options['temp-keys'] ?? null);

    $rows = readCandidateRows($candidateCsv);
    $results = [];
    $summary = [
        'total_rows' => 0,
        'permanent_present' => 0,
        'copy_from_legacy' => 0,
        'copy_from_temp' => 0,
        'request_reupload' => 0,
        'invalid_empty_filename' => 0,
    ];

    foreach ($rows as $row) {
        $result = classifyCandidateRow($row, $permanentKeys, $legacyKeys, $tempKeys);
        $results[] = $result;
        $summary['total_rows']++;
        $summary[$result['status']]++;
    }

    $payload = [
        'generated_at' => gmdate('c'),
        'mode' => 'offline_no_database_no_aws',
        'inputs' => [
            'candidate_csv' => $candidateCsv,
            'permanent_keys' => $options['permanent-keys'] ?? null,
            'legacy_keys' => $options['legacy-keys'] ?? null,
            'temp_keys' => $options['temp-keys'] ?? null,
        ],
        'summary' => $summary,
        'rows' => $results,
    ];

    $output = $format === 'csv' ? toCsv($results) : json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

    if ($outputPath) {
        file_put_contents($outputPath, $output);
        echo "Wrote {$outputPath}\n";
        return;
    }

    echo $output;
}

function parseOptions(array $argv): array
{
    $options = [];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
            continue;
        }

        if (!str_starts_with($arg, '--') || !str_contains($arg, '=')) {
            fwrite(STDERR, "Invalid argument: {$arg}\n\n");
            printUsage();
            exit(EXIT_USAGE);
        }

        [$key, $value] = explode('=', substr($arg, 2), 2);
        $options[$key] = $value;
    }

    return $options;
}

function requireOption(array $options, string $name): string
{
    if (empty($options[$name])) {
        fwrite(STDERR, "Missing required --{$name}=<path>\n\n");
        printUsage();
        exit(EXIT_USAGE);
    }

    $path = $options[$name];
    if (!is_readable($path)) {
        fwrite(STDERR, "File is not readable: {$path}\n");
        exit(EXIT_USAGE);
    }

    return $path;
}

function printUsage(): void
{
    echo <<<TEXT
Usage:
  php tools/audit-civil-id-files.php \
    --candidate-csv=/path/to/candidate-civil-id-export.csv \
    --permanent-keys=/path/to/studenthub-uploads-keys.csv \
    --legacy-keys=/path/to/legacy-civil-id-keys.csv \
    --temp-keys=/path/to/temp-bucket-keys.csv \
    --output=/path/to/recovery-plan.json

Inputs:
  --candidate-csv  CSV export with candidate_id, side, filename, expected_s3_key, candidate_updated_at.
  --permanent-keys Optional CSV or newline list of keys currently in studenthub-uploads.
  --legacy-keys    Optional CSV or newline list for candidate-civil-id/ legacy keys.
  --temp-keys      Optional CSV or newline list for temp bucket keys.
  --format         json (default) or csv.

This is an offline planner. It never queries production DBs, S3, AWS, or live accounts.

TEXT;
}

function readCandidateRows(string $path): array
{
    $handle = fopen($path, 'rb');
    if (!$handle) {
        fwrite(STDERR, "Unable to open {$path}\n");
        exit(EXIT_USAGE);
    }

    $header = readCsvRow($handle);
    if (!$header) {
        fwrite(STDERR, "Candidate CSV is empty: {$path}\n");
        exit(EXIT_USAGE);
    }

    $header = array_map(static fn ($value) => normalizeHeader((string) $value), $header);
    $required = ['candidate_id', 'side', 'filename'];
    foreach ($required as $column) {
        if (!in_array($column, $header, true)) {
            fwrite(STDERR, "Candidate CSV missing required column: {$column}\n");
            exit(EXIT_USAGE);
        }
    }

    $rows = [];
    while (($data = readCsvRow($handle)) !== false) {
        if ($data === [null] || $data === false) {
            continue;
        }

        $row = [];
        foreach ($header as $index => $column) {
            $row[$column] = isset($data[$index]) ? trim((string) $data[$index]) : '';
        }
        $rows[] = $row;
    }

    fclose($handle);
    return $rows;
}

function readKeySet(?string $path): array
{
    if (!$path) {
        return [];
    }

    if (!is_readable($path)) {
        fwrite(STDERR, "Key list is not readable: {$path}\n");
        exit(EXIT_USAGE);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return [];
    }

    $firstLine = trim((string) $lines[0]);
    $keys = [];

    if (str_contains($firstLine, ',')) {
        $handle = fopen($path, 'rb');
        $header = readCsvRow($handle);
        $header = array_map(static fn ($value) => normalizeHeader((string) $value), $header ?: []);
        $keyIndex = array_search('key', $header, true);
        if ($keyIndex === false) {
            $keyIndex = array_search('s3_key', $header, true);
        }
        if ($keyIndex === false) {
            $keyIndex = 0;
        }

        while (($row = readCsvRow($handle)) !== false) {
            if (isset($row[$keyIndex])) {
                addKeyVariants($keys, (string) $row[$keyIndex]);
            }
        }
        fclose($handle);
        return $keys;
    }

    foreach ($lines as $line) {
        addKeyVariants($keys, $line);
    }

    return $keys;
}

function classifyCandidateRow(array $row, array $permanentKeys, array $legacyKeys, array $tempKeys): array
{
    $filename = normalizeFilename($row['filename'] ?? '');
    $expectedKey = normalizeKey($row['expected_s3_key'] ?? '');
    if ($expectedKey === '' && $filename !== '') {
        $expectedKey = toPhotosKey($filename);
    }

    $legacyKey = toLegacyCivilKey($filename);
    $tempCandidates = tempKeyCandidates($filename);

    $status = 'request_reupload';
    $action = 'Flag candidate for re-upload; do not clear database fields until manual review is complete.';
    $sourceKey = null;
    $targetKey = $expectedKey;

    if ($filename === '') {
        $status = 'invalid_empty_filename';
        $action = 'Review export row; filename is empty.';
        $targetKey = null;
    } elseif (hasKey($permanentKeys, $expectedKey)) {
        $status = 'permanent_present';
        $action = 'No recovery action needed.';
        $sourceKey = $expectedKey;
    } elseif (hasKey($legacyKeys, $legacyKey) || hasKey($permanentKeys, $legacyKey)) {
        $status = 'copy_from_legacy';
        $action = 'Copy legacy object to expected photos/ key, then verify before changing any DB state.';
        $sourceKey = hasKey($legacyKeys, $legacyKey) ? $legacyKey : $legacyKey;
    } else {
        foreach ($tempCandidates as $candidate) {
            if (hasKey($tempKeys, $candidate)) {
                $status = 'copy_from_temp';
                $action = 'Copy temp-bucket object to expected photos/ key, then verify before changing any DB state.';
                $sourceKey = $candidate;
                break;
            }
        }
    }

    return [
        'candidate_id' => $row['candidate_id'] ?? '',
        'side' => $row['side'] ?? '',
        'filename' => $filename,
        'expected_s3_key' => $expectedKey,
        'status' => $status,
        'source_key' => $sourceKey,
        'target_key' => $targetKey,
        'candidate_updated_at' => $row['candidate_updated_at'] ?? '',
        'recommended_action' => $action,
    ];
}

function normalizeHeader(string $value): string
{
    return strtolower(trim(str_replace([' ', '-'], '_', $value)));
}

function readCsvRow($handle): array|false
{
    return fgetcsv($handle, null, ',', '"', '\\');
}

function normalizeFilename(string $value): string
{
    $value = trim($value);
    $value = preg_replace('#^https?://[^/]+/#', '', $value) ?? $value;
    $value = ltrim($value, '/');

    return $value;
}

function normalizeKey(string $value): string
{
    return ltrim(normalizeFilename($value), '/');
}

function toPhotosKey(string $filename): string
{
    $filename = normalizeKey($filename);
    if ($filename === '') {
        return '';
    }
    if (str_starts_with($filename, 'photos/')) {
        return $filename;
    }

    return 'photos/' . basename($filename);
}

function toLegacyCivilKey(string $filename): string
{
    $filename = normalizeKey($filename);
    if ($filename === '') {
        return '';
    }
    if (str_starts_with($filename, 'candidate-civil-id/')) {
        return $filename;
    }

    return 'candidate-civil-id/' . basename($filename);
}

function tempKeyCandidates(string $filename): array
{
    $filename = normalizeKey($filename);
    if ($filename === '') {
        return [];
    }

    $basename = basename($filename);
    $candidates = array_unique([
        $filename,
        $basename,
        'photos/' . $basename,
        'candidate-civil-id/' . $basename,
    ]);

    return array_values(array_filter($candidates));
}

function addKeyVariants(array &$keys, string $key): void
{
    $key = normalizeKey($key);
    if ($key === '') {
        return;
    }

    $keys[$key] = true;
    $keys[basename($key)] = true;
}

function hasKey(array $keys, string $key): bool
{
    $key = normalizeKey($key);
    if ($key === '') {
        return false;
    }

    return isset($keys[$key]) || isset($keys[basename($key)]);
}

function toCsv(array $rows): string
{
    $handle = fopen('php://temp', 'rb+');
    $columns = [
        'candidate_id',
        'side',
        'filename',
        'expected_s3_key',
        'status',
        'source_key',
        'target_key',
        'candidate_updated_at',
        'recommended_action',
    ];

    fputcsv($handle, $columns, ',', '"', '\\');
    foreach ($rows as $row) {
        fputcsv($handle, array_map(static fn ($column) => $row[$column] ?? '', $columns), ',', '"', '\\');
    }

    rewind($handle);
    return stream_get_contents($handle) ?: '';
}
