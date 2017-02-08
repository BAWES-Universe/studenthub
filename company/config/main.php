<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-company',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'company\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'company\modules\v1\Module',
        ],
    ],
    'components' => [
        'request' => [
            // Accept and parse JSON Requests
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'user' => [
            'identityClass' => 'common\models\Company',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                [ // AuthController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/auth',
                    'pluralize' => false,
                    'patterns' => [
                        'GET login' => 'login',
                        'PATCH verify' => 'verify-email',
                        'PATCH update-password' => 'update-password',
                        'POST create-account' => 'create-account',
                        'POST request-reset-password' => 'request-reset-password',
                        'POST resend-verification-email' => 'resend-verification-email',
                        'POST validate' => 'validate',
                        // OPTIONS VERBS
                        'OPTIONS verify' => 'options',
                        'OPTIONS validate' => 'options',
                        'OPTIONS login' => 'options',
                        'OPTIONS create-account' => 'options',
                        'OPTIONS request-reset-password' => 'options',
                        'OPTIONS resend-verification-email' => 'options',
                    ]
                ],
                [ // AccountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/account',
                    'patterns' => [
                        'GET' => 'list',
                        'GET stats' => 'stats',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS stats' => 'options',
                    ]
                ],
            ],
        ],
    ],
    'params' => $params,
];
