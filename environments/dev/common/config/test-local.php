<?php
return yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/main.php'),
    require(__DIR__ . '/main-local.php'),
    require(__DIR__ . '/test.php'),
    [
        'components' => [
            'db' => [
                'dsn' => 'mysql:host=localhost;dbname=payroll_test',
            ],
            'walletDb' => [
                'class' => 'yii\db\Connection',
                'dsn' => 'mysql:host=localhost;dbname=wallet_test',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8',
            ],
            'cache' => [
                'class' => 'yii\caching\FileCache',
            ],
        ],
    ]
);
