<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-admin',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'admin\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'admin\modules\v1\Module',
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
            'identityClass' => 'common\models\Admin',
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
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                    ]
                ],
                [ // StatisticController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/statistic',
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // StaffController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff',
                    'patterns' => [
                        'GET' => 'list',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                    ]
                ],
                [ // CompanyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'GET sub-companies/<id>' => 'sub-companies',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS sub-companies/<id>' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                    ]
                ],
                [ // StoreController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/store',
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options'
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET search' => 'search',
                        'GET total-to-review' => 'total-to-review',
                        'PATCH approve/<id>' => 'approve',
                        'GET transfers/<id>' => 'transfers',
                        'GET work-history/<id>' => 'work-history',
                        // OPTIONS VERBS
                        'OPTIONS search' => 'options',
                        'OPTIONS total-to-review' => 'options',
                        'OPTIONS approve/<id>' => 'options',
                        'OPTIONS transfers/<id>' => 'options',
                        'OPTIONS work-history/<id>' => 'options',
                    ]
                ],
                [ // TransferController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer',
                    'patterns' => [
                        'GET' => 'list',
                        'GET text' => 'text',
                        'GET payable-candidates' => 'payable-candidates',
                        'GET export-payable-candidates' => 'export-payable-candidates',
                        'GET export/<id>' => 'export',
                        'GET pdf/<id>' => 'pdf',
                        'GET <id>' => 'view',
                        'PATCH payment-received-distributing/<id>' => 'payment-received-distributing',
                        'PATCH unlock/<id>' => 'unlock',
                        'PATCH lock/<id>' => 'lock',
                        'PATCH mark-paid-all' => 'mark-paid-all',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS payable-candidates' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS payment-received-distributing/<id>' => 'options',
                        'OPTIONS unlock/<id>' => 'options',
                        'OPTIONS lock/<id>' => 'options',
                        'OPTIONS mark-paid-all' => 'options',
                        'OPTIONS export-payable-candidates' => 'options',
                        'OPTIONS text' => 'options',
                        'OPTIONS export/<id>' => 'options',
                        'OPTIONS pdf/<id>' => 'options',
                    ]
                ],
                [ // TransferCandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer-candidate',
                    'patterns' => [
                        'GET' => 'list',
                        'PATCH unpaid/<id>' => 'unpaid',
                        'PATCH paid/<id>' => 'paid',
                        'PATCH mark-paid-all' => 'mark-paid-all',
                        'PATCH mark-unpaid-all' => 'mark-unpaid-all',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS unpaid/<id>' => 'options',
                        'OPTIONS paid/<id>' => 'options',
                        'OPTIONS mark-paid-all' => 'options',
                        'OPTIONS mark-unpaid-all' => 'options',
                    ]
                ],
                [ // BankController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/bank',
                    'patterns' => [
                        'GET' => 'list',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // UniversityController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/university',
                    'patterns' => [
                        'GET' => 'list',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // CountryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/country',
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
