<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    file_exists(__DIR__ . '/../../common/config/params-local.php') ? require(__DIR__ . '/../../common/config/params-local.php') : [],
    require(__DIR__ . '/params.php'),
    file_exists(__DIR__ . '/params-local.php') ? require(__DIR__ . '/params-local.php') : []
);

return [
    'id' => 'app-apidoc',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'apidoc\controllers',
    'viewPath' => '@app/views',
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            'enableCookieValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                'openapi.json' => 'api/openapi',
                '' => 'api/index',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ],
        ],
    ],
    'params' => $params,
];

