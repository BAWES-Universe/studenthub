#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$workDir = sys_get_temp_dir() . '/civil-id-audit-test-' . bin2hex(random_bytes(4));
mkdir($workDir);

try {
    writeFile($workDir . '/candidates.csv', <<<CSV
candidate_id,side,filename,expected_s3_key,candidate_updated_at
1,front,front-ok.png,photos/front-ok.png,2026-05-01
2,back,back-legacy.png,photos/back-legacy.png,2026-05-02
3,front,temp-file.png,photos/temp-file.png,2026-05-03
4,back,missing-file.png,photos/missing-file.png,2026-05-04
5,front,,,2026-05-05
CSV);

    writeFile($workDir . '/permanent.csv', "Key\nphotos/front-ok.png\n");
    writeFile($workDir . '/legacy.txt', "candidate-civil-id/back-legacy.png\n");
    writeFile($workDir . '/temp.txt', "temp-file.png\n");

    $command = sprintf(
        'php %s --candidate-csv=%s --permanent-keys=%s --legacy-keys=%s --temp-keys=%s',
        escapeshellarg($root . '/tools/audit-civil-id-files.php'),
        escapeshellarg($workDir . '/candidates.csv'),
        escapeshellarg($workDir . '/permanent.csv'),
        escapeshellarg($workDir . '/legacy.txt'),
        escapeshellarg($workDir . '/temp.txt')
    );

    exec($command, $lines, $exitCode);
    assertSame(0, $exitCode, 'audit command exit code');

    $payload = json_decode(implode("\n", $lines), true);
    if (!is_array($payload)) {
        fail('audit output is not JSON');
    }

    assertSame('offline_no_database_no_aws', $payload['mode'] ?? null, 'offline mode');
    assertSame([
        'total_rows' => 5,
        'permanent_present' => 1,
        'copy_from_legacy' => 1,
        'copy_from_temp' => 1,
        'request_reupload' => 1,
        'invalid_empty_filename' => 1,
    ], $payload['summary'] ?? null, 'summary counts');

    $statuses = array_column($payload['rows'] ?? [], 'status', 'candidate_id');
    assertSame('permanent_present', $statuses['1'] ?? null, 'candidate 1 status');
    assertSame('copy_from_legacy', $statuses['2'] ?? null, 'candidate 2 status');
    assertSame('copy_from_temp', $statuses['3'] ?? null, 'candidate 3 status');
    assertSame('request_reupload', $statuses['4'] ?? null, 'candidate 4 status');
    assertSame('invalid_empty_filename', $statuses['5'] ?? null, 'candidate 5 status');

    echo "audit-civil-id-files test passed\n";
} finally {
    removeDirectory($workDir);
}

function writeFile(string $path, string $contents): void
{
    file_put_contents($path, $contents);
}

function assertSame(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fail($label . ' expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function removeDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    foreach (scandir($path) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $child = $path . DIRECTORY_SEPARATOR . $entry;
        is_dir($child) ? removeDirectory($child) : unlink($child);
    }

    rmdir($path);
}
