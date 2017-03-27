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
            'identityClass' => 'company\models\Company',
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
                        'POST request-reset-password' => 'request-reset-password',
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        'OPTIONS request-reset-password' => 'options',
                        'OPTIONS update-password' => 'options',
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET' => 'list',
                        'POST filter' => 'filter',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS filter' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // InvoiceController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/invoice',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'GET pdf/<id>' => 'pdf',
                        'POST' => 'create',
                        'PATCH lock/<id>' => 'lock',
                        'PATCH payment-sent/<id>' => 'payment-sent',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS lock/<id>' => 'options',
                        'OPTIONS payment-sent/<id>' => 'options'
                    ]
                ],
                [ // StoreController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/store',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <companyId>' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <companyId>' => 'options'
                    ]
                ],
                [ // CompanyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company',
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options'
                    ]
                ],
            ],
        ],
    ],
    'params' => $params,
];
