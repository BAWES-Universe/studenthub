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
            'enableCookieValidation' => false,
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
            'traceLevel' => YII_DEBUG ? 3 : 0
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
                        'GET transfer' => 'transfer',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS transfer' => 'options',
                    ]
                ],
                [ // StaffController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
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
                [ // AdminController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/admin',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
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
                [ // InspectorController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/inspector',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
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
                        'GET followups' => 'followups',
                        'GET sub-companies/<id>' => 'sub-companies',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'POST file-create/<id>' => 'create-file',
                        'PATCH file-update/<id>' => 'update-file',
                        'PATCH change-status/<id>' => 'change-status',
                        'PATCH update-followup/<id>' => 'update-followup',
                        'PATCH update-followup-interval/<id>' => 'update-followup-interval',
                        'PATCH <id>' => 'update',
                        'DELETE remove-file/<id>' => 'delete-file',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS followups' => 'options',
                        'OPTIONS sub-companies/<id>' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                        'OPTIONS file-create/<id>' => 'options',
                        'OPTIONS file-update/<id>' => 'options',
                        'OPTIONS remove-file/<id>' => 'options',
                        'OPTIONS change-status/<id>' => 'options',
                        'OPTIONS update-followup/<id>' => 'options',
                        'OPTIONS update-followup-interval/<id>' => 'options',
                    ]
                ],
                [ // StoreController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/store',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options'
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
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS search' => 'options',
                        'OPTIONS total-to-review' => 'options',
                        'OPTIONS approve/<id>' => 'options',
                        'OPTIONS transfers/<id>' => 'options',
                        'OPTIONS work-history/<id>' => 'options',
                        'OPTIONS <id>' => 'options'
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
                [ // TransferController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer',
                    'patterns' => [
                        'GET suspicious' => 'suspicious-list',
                        'GET' => 'list',
                        'GET text' => 'text',
                        'GET payable-candidates' => 'payable-candidates',
                        'GET export-payable-candidates' => 'export-payable-candidates',
                        'GET invoices/<id>' => 'invoices',
                        'GET export/<id>' => 'export',
                        'GET pdf/<id>/<type>' => 'pdf',
                        'GET <id>' => 'view',
                        'POST import-excel' => 'import-excel',
                        'POST update-transfer-from-file/<id>' => 'update-transfer-from-file',
                        'PATCH payment-received-distributing/<id>' => 'payment-received-distributing',
                        'PATCH unlock/<id>' => 'unlock',
                        'PATCH lock/<id>' => 'lock',
                        'PATCH mark-paid-all' => 'mark-paid-all',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS update-transfer-from-file/<id>' => 'options',
                        'OPTIONS payable-candidates' => 'options',
                        'OPTIONS invoices/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS import-excel' => 'options',
                        'OPTIONS payment-received-distributing/<id>' => 'options',
                        'OPTIONS unlock/<id>' => 'options',
                        'OPTIONS lock/<id>' => 'options',
                        'OPTIONS mark-paid-all' => 'options',
                        'OPTIONS export-payable-candidates' => 'options',
                        'OPTIONS text' => 'options',
                        'OPTIONS export/<id>' => 'options',
                        'OPTIONS pdf/<id>/<type>' => 'options',
                        'OPTIONS suspicious' => 'options'
                    ]
                ],
                [ // TransferCandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer-candidate',
                    'patterns' => [
                        'GET' => 'list',
                        'GET by-transfer/<id>' => 'by-transfer',
                        'GET by-transfer-file/<id>' => 'by-transfer-file',
                        'GET <id>' => 'view',
                        'PATCH unpaid/<id>' => 'unpaid',
                        'PATCH paid/<id>' => 'paid',
                        'PATCH mark-paid-all' => 'mark-paid-all',
                        'PATCH mark-unpaid-all' => 'mark-unpaid-all',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS by-transfer/<id>' => 'options',
                        'OPTIONS by-transfer-file/<id>' => 'options',
                        'OPTIONS unpaid/<id>' => 'options',
                        'OPTIONS paid/<id>' => 'options',
                        'OPTIONS mark-paid-all' => 'options',
                        'OPTIONS mark-unpaid-all' => 'options',
                    ]
                ],
                [ // TransferFileController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer-file',
                    'patterns' => [
                        'GET' => 'list', 
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // ExpenseController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/expense',
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
                [ // BankController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/bank',
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
                [ // BrandController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/brand',
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
                [ // CompanyContactController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company-contact',
                    'patterns' => [
                        'GET' => 'list',
                        'GET view-company-contact' => 'view-company-contact',
                        'GET is-email-exists' => 'is-email-exists',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH add-to-team' => 'add-to-team',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS view-company-contact' => 'options',
                        'OPTIONS add-to-team' => 'options',
                        'OPTIONS is-email-exists' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // RequestChecklistController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request-checklist',
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
                [ // UniversityController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/university',
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
                [ // CountryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/country',
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
            ],
        ],
    ],
    'params' => $params,
];
