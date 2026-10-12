<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

$consoleSlackWebhookUrl = getenv('CONSOLE_SLACK_WEBHOOK_URL');
if (!$consoleSlackWebhookUrl) {
    $consoleSlackWebhookUrl = getenv('SLACK_WEBHOOK_URL') ?: '';
}

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\faker\FixtureController',
            'namespace' => 'common\fixtures',
            'templatePath' => '@common/fixtures/templates',
            'fixtureDataPath' => '@common/fixtures/data',
        ],
    ],
    'components' => [
        'httpclient' => [
            'class' => 'yii\httpclient\Client',
        ],

        'slack' => [
            'class' => 'understeam\slack\Client',
            'url' => $consoleSlackWebhookUrl,
            'username' => 'StudentHub',
        ],
        'log' => [
            'targets' => [
                /*[
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                ],*/
                [
                    'class' => 'common\components\SlackLogger',
                    'logVars' => [],
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['admin\*', 'candidate\*', 'company\*', 'staff\*', 'common\*', 'console\*'],
                ],
            ],
        ],
        'config' => [
            'class' => 'common\components\Config',
        ],
    ],
    'params' => $params,
];
