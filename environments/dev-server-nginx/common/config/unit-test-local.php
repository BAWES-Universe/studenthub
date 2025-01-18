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
            //to fix error for BlamableBehaviour in unit testing
            'user' => [
                'class' => 'common\models\Staff'
            ]
        ],
    ]
);
