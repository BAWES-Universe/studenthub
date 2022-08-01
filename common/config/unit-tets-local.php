<?php
return yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/main.php'),
    require(__DIR__ . '/main-local.php'),
    require(__DIR__ . '/test.php'),
    [
        'components' => [
            'db' => [
                'class' => 'yii\db\Connection',
                'dsn' => 'mysql:host=localhost:8889;dbname=studenthub_test',
                'username' => 'root',
                'password' => 'root',
                'charset' => 'utf8',
            ],
            //to fix error for BlamableBehaviour in unit testing
            'user' => [
                'identityClass' => 'common\models\Staff', // User must implement the IdentityInterface
                'enableAutoLogin' => true,
            ]
        ],
    ]
);
