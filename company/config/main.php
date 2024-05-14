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
            'enableCookieValidation' => false,
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
                [// CampaignController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/campaign',
                    'patterns' => [
                        'PATCH click/<id>' => 'click',
                        // OPTIONS VERBS
                        'OPTIONS click/<id>' => 'options',
                    ]
                ],
                [ // PingController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/ping',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'test',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // CurrencyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/currency',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // CountryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/country',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
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
                        'GET locate' => 'locate',
                        'POST create-account' => 'create-account',
                        'POST request-reset-password' => 'request-reset-password',
                        'POST verify-email' => 'verify-email',
                        'POST is-email-verified' => 'is-email-verified',
                        'POST update-email' => 'update-email',
                        'POST resend-verification-email' => 'resend-verification-email',
                        'POST login-auth0' => 'login-auth0',
                        'POST login-by-google' => 'login-by-google',
                        'POST login-by-key' => 'login-by-key',
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        'OPTIONS locate' => 'options',
                        'OPTIONS request-reset-password' => 'options',
                        'OPTIONS verify-email' => 'options',
                        'OPTIONS is-email-verified' => 'options',
                        'OPTIONS update-email' => 'options',
                        'OPTIONS resend-verification-email' => 'options',
                        'OPTIONS update-password' => 'options',
                        'OPTIONS login-auth0' => 'options',
                        'OPTIONS login-by-google' => 'options',
                        'OPTIONS login-by-key' => 'options',
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
                        'GET search' => 'search',
                        'GET total' => 'total',
                        'GET work-history/<id>' => 'work-history',
                        "GET applications/<candidate_id>" => "applications",
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        "OPTIONS applications/<candidate_id>" => "options",
                        'OPTIONS' => 'options',
                        'OPTIONS total' => 'options',
                        'OPTIONS search' => 'options',
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
                        'PATCH cancel/<id>' => 'cancel',
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
                        'OPTIONS cancel/<id>' => 'options',
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
                        "POST store-assignment-request" => "store-assignment-request",
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',                        
                        'OPTIONS company-store' => 'options',
                        "OPTIONS store-assignment-request" => "options",
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
                        'PATCH activate' => 'activate',
                        'PATCH update-logo' => 'update-logo',
                        'PATCH' => 'update',
                        'DELETE remove-logo' => 'remove-logo',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS activate' => 'options',
                        'OPTIONS list-child' => 'options',
                        'OPTIONS remove-logo' => 'options',
                        'OPTIONS update-logo' => 'options',
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
                        'POST update-email' => 'update-email',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS update' => 'options',
                        'OPTIONS view' => 'options',
                        'OPTIONS change-password' => 'options',
                        'OPTIONS update-email' => 'options',
                    ]
                ],
                [ // RequestController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request',
                    'patterns' => [
                        'GET' => 'list',
                        'GET active' => 'list-active',
                        'GET count' => 'request-count',
                        'GET is-request-updated/<id>' => 'is-request-updated',
                        'GET applications/<request_uuid>' => 'applications',
                        'GET <id>' => 'view',
                        "POST request-interview" => "request-interview",
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS count' => 'options',
                        'OPTIONS active' => 'options',
                        "OPTIONS request-interview" => 'options',
                        'OPTIONS is-request-updated/<id>' => 'options',
                        'OPTIONS applications/<request_uuid>' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // SuggestionController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/suggestion',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'PATCH accept/<id>' => 'accept',
                        'PATCH reject/<id>' => 'reject',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS accept/<id>' => 'options',
                        'OPTIONS reject/<id>' => 'options',
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
                        'GET view-company-contact' => 'view-company-contact',
                        'GET <id>' => 'view',
                        //'DELETE <id>' => 'remove-member',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS view-company-contact' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // InvitationController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request-candidate-invitation',
                    'pluralize' => false,
                    'patterns' => [
                        'POST' => 'create',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                /*[ // InvitationController
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
                ],*/
                [ // CandidateWorkingHourController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate-working-hour',
                    'patterns' => [
                        'GET date' => 'list-date',
                        'GET hour' => 'list-hour',
                        // OPTIONS VERBS
                        'OPTIONS date' => 'options',
                        'OPTIONS hour' => 'options',
                    ]
                ],
                [ // BalanceController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/balance',
                    'pluralize' => false,
                    'patterns' => [
                        'GET payable-list' => 'payable-list',
                        'POST init-transfer' => 'init-transfer',
                        'PATCH pay-by-wallet' => 'pay-by-wallet',
                        // OPTIONS VERBS
                        'OPTIONS pay-by-wallet' => 'options',
                        'OPTIONS init-transfer' => 'options',
                        'OPTIONS payable-list' => 'options',
                    ]
                ],
            ],
        ],
    ],
    'params' => $params,
];
