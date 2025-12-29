<?php
return yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/main.php'),
    require(__DIR__ . '/main-local.php'),
    require(__DIR__ . '/test.php'),
    [
        'components' => [
            'db' => [
                'class' => 'yii\db\Connection',
                'dsn' => 'mysql:host=' . (getenv('DB_HOST') ?: 'mysql') . ';port=3306;dbname=' . (getenv('TEST_DB_NAME') ?: 'studenthub_test'),
                'username' => getenv('DB_USER') ?: 'studenthubuser',
                'password' => getenv('DB_PASSWORD') ?: 'studenthub',
                'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
            ],
            'cache' => [
                'class' => 'yii\caching\FileCache',
            ],
        ],
    ]
);
