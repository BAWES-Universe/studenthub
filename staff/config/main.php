<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-staff',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'staff\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'staff\modules\v1\Module',
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
            'identityClass' => 'common\models\Staff',
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
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        'OPTIONS update-password' => 'options',
                    ]
                ],     
                [ // AccountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/account',
                    'pluralize' => false,
                    'patterns' => [
                        'POST update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS update-password' => 'options',
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
                [ // CompanyContactController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company-contact',
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
                        'GET detail/<id>' => 'view',
                        'GET not-assigned' => 'list-not-assigned',
                        'GET assigned' => 'list-assigned',                        
                        'GET assigned-without-bank' => 'list-assigned-without-bank-info',
                        'GET not-assigned-without-bank' => 'list-not-assigned-without-bank-info',
                        'GET search' => 'search',
                        'GET transfers/<id>' => 'transfers',
                        'GET work-history/<id>' => 'work-history',
                        'GET total-to-review' => 'total-to-review',
                        'POST' => 'create',
                        'PATCH assign/<id>' => 'assign',
                        'PATCH update-hour-rate/<id>' => 'update-candidate-hour-rate',
                        'PATCH job-search-status' => 'job-search-status',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'PATCH <id>' => 'update',
                        'PATCH approve/<id>' => 'approve',
                        'DELETE unassign/<id>' => 'unassign',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS detail/<id>' => 'options',
                        'OPTIONS assign/<id>' => 'options',
                        'OPTIONS unassign/<id>' => 'options',                        
                        'OPTIONS not-assigned' => 'options',
                        'OPTIONS assigned' => 'options',                        
                        'OPTIONS search' => 'options',       
                        'OPTIONS job-search-status' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                        'OPTIONS transfers/<id>' => 'options',
                        'OPTIONS work-history/<id>' => 'options',
                        'OPTIONS assigned-without-bank' => 'options',
                        'OPTIONS not-assigned-without-bank' => 'options',
                        'OPTIONS total-to-review' => 'options',
                        'OPTIONS approve/<id>' => 'options',
                        'OPTIONS update-hour-rate/<id>' => 'options',
                    ]
                ],
                [ // StoreController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/store',
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
                [ // CompanyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company',
                    'patterns' => [
                        'GET' => 'list',
                        'GET followups' => 'followups',
                        'GET <id>' => 'view',
                        'POST file-create/<id>' => 'create-file',
                        'POST add-followup-note/<id>' => 'add-followup-note',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS followups' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS file-create/<id>' => 'options',
                        'OPTIONS add-followup-note/<id>' => 'options'
                    ]
                ],
                [ // BankController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/bank',
                    'patterns' => [
                        'GET' => 'list',
                        'GET all' => 'all',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS all' => 'options'
                    ]
                ],
                [ // UniversityController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/university',
                    'patterns' => [
                        'GET' => 'list',
                        'GET all' => 'all',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS all' => 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // CountryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/country',
                    'patterns' => [
                        'GET' => 'list',
                        'GET all' => 'all',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS all' => 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // CandidateIdCardController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate-id-card',
                    'patterns' => [
                        'GET list-candidate-ids' => 'list-candidate-ids',
                        'GET list-candidates' => 'list-candidates',
                        'POST generate' => 'generate',                        
                        'GET list-expired' => 'list-expired',
                        'POST renew' => 'renew',                                                
                        'GET total-expired' => 'total-expired',
                        'GET <id>/<token>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS list-candidate-ids' => 'options',
                        'OPTIONS list-candidates' => 'options',
                        'OPTIONS generate' => 'options',
                        'OPTIONS list-expired' => 'options',
                        'OPTIONS renew' => 'options',                        
                        'OPTIONS total-expired' => 'options',
                        'OPTIONS <id>/<token>' => 'options'
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
            ],
        ],
    ],
    'params' => $params,
];
