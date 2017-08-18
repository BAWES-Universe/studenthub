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
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        'OPTIONS update-password' => 'options',
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET' => 'list',
                        'GET total' => 'total',
                        'GET work-history/<id>' => 'work-history',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS total' => 'options',
                        'OPTIONS work-history/<id>' => 'options',
                    ]
                ],
                [ // TransferController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer',
                    'patterns' => [
                        'GET' => 'list',
                        //'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'edit',
                        'PATCH payment-sent/<id>' => 'payment-sent',                        
                        'PATCH lock/<id>' => 'lock',
                        'DELETE <id>' => 'delete',                        
                        'GET pdf/<id>' => 'pdf',
                        'GET excel-template' => 'excel-template',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS payment-sent/<id>' => 'options',
                        'OPTIONS lock/<id>' => 'options',
                        'OPTIONS pdf/<id>' => 'options',
                        'OPTIONS excel-template' => 'options'
                    ]
                ],
                [ // StoreController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/store',
                    'patterns' => [
                        'GET' => 'list',
                        'GET company-store' => 'index',
                        'GET <companyId>' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',                        
                        'OPTIONS company-store' => 'options',
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
                [ // AccountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/account',
                    'pluralize' => false,
                    'patterns' => [
                        'POST change-password' => 'change-password',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS change-password' => 'options'
                    ]
                ],
            ],
        ],
    ],
    'params' => $params,
];
