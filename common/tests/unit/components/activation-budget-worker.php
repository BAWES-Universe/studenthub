<?php

/**
 * One concurrent activation guess. Prints DENIED or LIMITED.
 * The cache directory is the first argument and is shared by the parent test.
 */

$root = dirname(__DIR__, 4);

require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'common\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = $root . '/common/' . $relative . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$cacheDir = isset($argv[1]) ? $argv[1] : '';
if ($cacheDir === '') {
    fwrite(STDERR, "cache directory is required\n");
    exit(1);
}

new yii\console\Application([
    'id' => 'ce37-activation-worker',
    'basePath' => $root . '/common',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => $cacheDir,
        ],
    ],
]);

$authorizer = new common\components\ActivationPresignAuthorizer(
    Yii::$app->cache,
    static function () {
        return [];
    }
);

try {
    $authorizer->authorize('owner@example.test', '0000', 42, '203.0.113.8');
    fwrite(STDERR, "a wrong key was accepted\n");
    exit(1);
} catch (common\components\ActivationPresignDeniedException $e) {
    echo "DENIED\n";
} catch (common\components\ActivationPresignLimitedException $e) {
    echo "LIMITED\n";
}
