<?php

$files = [
    'common/config/main.php',
    'environments/prod-railway/common/config/main-local.php'
];

$patterns = [
    'AKIAWMITDJRKVN5ODY2X',
    'zAr8Xov1olqBAaiE8CX+j45qDHaAbO+S3EhUVeaT',
    'AKIAWMITDJRKWZZEWCUM',
    'M6olF9l1pZ1sKIswrSCjKtGkAG2w9qDV9x230UlI'
];

$envVars = [
    'AWS_TEMP_BUCKET_KEY',
    'AWS_TEMP_BUCKET_SECRET',
    'AWS_PERMANENT_S3_ACCESS_KEY_ID',
    'AWS_PERMANENT_S3_SECRET_ACCESS_KEY'
];

$errors = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        $errors[] = "Could not read file: $file";
        continue;
    }

    foreach ($patterns as $pattern) {
        if (strpos($content, $pattern) !== false) {
            $errors[] = "Found hardcoded credential '$pattern' in $file";
        }
    }

    foreach ($envVars as $envVar) {
        if (strpos($content, "getenv('$envVar')") === false) {
            // Check if it's the right file for the env var
            if ($file === 'common/config/main.php' && (strpos($envVar, 'TEMP') !== false)) {
                 $errors[] = "Missing getenv('$envVar') in $file";
            }
            if ($file === 'environments/prod-railway/common/config/main-local.php' && (strpos($envVar, 'PERMANENT') !== false)) {
                 $errors[] = "Missing getenv('$envVar') in $file";
            }
        }
    }
}

if (empty($errors)) {
    echo "Verification PASSED: No hardcoded credentials found and environment variables are used.\n";
    exit(0);
} else {
    echo "Verification FAILED:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
    exit(1);
}
