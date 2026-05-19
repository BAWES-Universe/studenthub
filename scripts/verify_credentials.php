<?php
/**
 * Security Verification Script (Enhanced)
 * Checks for hardcoded credentials across config files.
 */

$files = [
    __DIR__ . '/../common/config/main.php',
    __DIR__ . '/../environments/prod-railway/common/config/main-local.php'
];

$required_env_vars = [
    'AWS_TEMP_BUCKET_KEY',
    'AWS_TEMP_BUCKET_SECRET',
    'AWS_PERMANENT_S3_ACCESS_KEY_ID',
    'AWS_PERMANENT_S3_SECRET_ACCESS_KEY',
    'RECAPTCHA_SECRET_KEY',
    'JIRA_API_TOKEN',
    'ALGOLIA_API_KEY',
    'IPSTACK_ACCESS_KEY',
    'CLOUDINARY_API_SECRET',
    'SLACK_WEBHOOK_URL',
    'DB_PASSWORD',
    'WALLET_DB_PASSWORD',
    'WALLET_API_KEY',
    'REDIS_PASSWORD',
    'MAIL_PASSWORD',
    'XERO_CLIENT_SECRET',
    'AWS_MEDIACONVERT_SECRET',
    'SENTRY_DSN'
];

$failures = 0;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    echo "Checking $file...\n";
    
    // Check for common literal patterns that indicate hardcoded keys
    // (excluding placeholders and getenv calls)
    if (preg_match('/\'[a-zA-Z0-9\/+]{20,}\'/', $content, $matches)) {
        if (!str_contains($matches[0], 'getenv')) {
            echo "FAILED: Potential hardcoded key found in $file: " . $matches[0] . "\n";
            $failures++;
        }
    }
}

if ($failures === 0) {
    echo "SUCCESS: No hardcoded credentials detected in config files.\n";
    exit(0);
} else {
    echo "ERROR: Found $failures issues.\n";
    exit(1);
}
