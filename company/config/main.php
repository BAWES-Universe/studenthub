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
            'identityClass' => 'company\models\Contact',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null
        ],
        'companyManager' => [ //Component for agent to manage Employers
            'class' => 'company\components\CompanyManager',
        ],
        'storeManager' => [ //Component for agent to manage stores
            'class' => 'company\components\StoreManager',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                [ // AlgoliaController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/algolia',
                    'pluralize' => false,
                    'patterns' => [
                        'GET key' => 'key',
                        // OPTIONS VERBS
                        'OPTIONS key' => 'options'
                    ]
                ],
                [ // AuthController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/auth',
                    'pluralize' => false,
                    'patterns' => [
                        'GET login' => 'login',
                        'POST create-account' => 'create-account',
                        'POST request-reset-password' => 'request-reset-password',
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        'OPTIONS request-reset-password' => 'options',
                        'OPTIONS update-password' => 'options',
                        'OPTIONS create-account' => 'options',
                    ]
                ],
                [ // NoteController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/note',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET' => 'list',
                        'GET total' => 'total',
                        'GET work-history/<id>' => 'work-history',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS total' => 'options',
                        'OPTIONS work-history/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // TransferController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer',
                    'patterns' => [
                        'GET' => 'list',
                        'GET transfer-excel-template' => 'transfer-excel-template',
                        'GET pdf/<id>' => 'pdf',                        
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'POST create-by-excel' => 'create-by-excel',
                        'PATCH payment-sent/<id>' => 'payment-sent',                        
                        'PATCH lock/<id>' => 'lock',
                        'PATCH edit-by-excel/<id>' => 'edit-by-excel',                        
                        'PATCH <id>' => 'edit',
                        'DELETE <id>' => 'delete',                        
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS transfer-excel-template' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS create-by-excel' => 'options',
                        'OPTIONS payment-sent/<id>' => 'options',
                        'OPTIONS lock/<id>' => 'options',
                        'OPTIONS edit-by-excel/<id>' => 'options',                        
                        'OPTIONS pdf/<id>' => 'options'
                    ]
                ],
                [ // StoreController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/store',
                    'patterns' => [
                        'GET' => 'list',
                        'GET company-store' => 'index',
                        'GET view/<id>' => 'view',
                        'GET <companyId>' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',                        
                        'OPTIONS company-store' => 'options',
                        'OPTIONS <companyId>' => 'options',
                        'OPTIONS view/<id>' => 'options',
                    ]
                ],
                [ // CompanyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company',
                    'patterns' => [
                        'GET' => 'list',
                        'GET list-child' => 'list-child',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS list-child' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // AccountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/account',
                    'pluralize' => false,
                    'patterns' => [
                        'GET view' => 'view',
                        'PATCH update' => 'update',
                        'POST change-password' => 'change-password',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS update' => 'options',
                        'OPTIONS view' => 'options',
                        'OPTIONS change-password' => 'options'
                    ]
                ],
                [ // RequestController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // SuggestionController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/suggestion',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // RequestActivityController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request-activity',
                    'pluralize' => false,
                    'patterns' => [
                        'GET request-activities/<id>' => 'request-activities',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS request-activities/<id>' => 'options'
                    ]
                ],
                [ // CompanyContactController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company-contact',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // InvitationController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/invitation',
                    'patterns' => [
                        'GET pending' => 'pending',
                        'GET invitation-list/<id>' => 'invitation-list',
                        'GET by-otp/<otp>' => 'by-otp',
                        'POST' => 'invite',
                        'PATCH accept/<id>' => 'accept',
                        'PATCH reject/<id>' => 'reject',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS pending' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS by-otp/<otp>' => 'options',
                        'OPTIONS accept/<id>' => 'options',
                        'OPTIONS reject/<id>' => 'options',
                        'OPTIONS invitation-list/<id>' => 'options',
                    ]
                ],
            ],
        ],
    ],
    'params' => $params,
];
