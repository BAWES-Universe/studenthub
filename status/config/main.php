<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

// Normalize allowedOrigins to array format for CORS filter
if (isset($params['allowedOrigins']) && !is_array($params['allowedOrigins'])) {
    $params['allowedOrigins'] = $params['allowedOrigins'] === '*' ? ['*'] : [$params['allowedOrigins']];
}

return [
    'id' => 'app-status',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'status\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'status\modules\v1\Module',
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
            'identityClass' => 'common\models\Inspector',
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
                [ // AWSController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/aws',
                    'pluralize' => false,
                    'patterns' => [
                        'GET config' => 'config',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // StatisticController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/statistic',
                    'patterns' => [
                        'GET' => 'list',
                        'GET graph' => 'graph',
                        'GET transfer' => 'transfer',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS graph' => 'options',
                        'OPTIONS transfer' => 'options',
                    ]
                ],
                [ // CompanyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company',
                    'patterns' => [
                        'GET' => 'list',
                        'GET sub-companies/<id>' => 'sub-companies',
                        'GET year-report' => 'year-report',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS sub-companies/<id>' => 'options',
                        'OPTIONS year-report' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET' => 'list',
                        'GET search' => 'search',
                        'GET transfers/<id>' => 'transfers',
                        'GET work-history/<id>' => 'work-history',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS search' => 'options',
                        'OPTIONS transfers/<id>' => 'options',
                        'OPTIONS work-history/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // TransferController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer',
                    'patterns' => [
                        'GET suspicious' => 'suspicious-list',
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
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
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS by-transfer/<id>' => 'options',
                        'OPTIONS by-transfer-file/<id>' => 'options',
                    ]
                ],
                [ // StaffController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff',
                    'patterns' => [
                        'GET' => 'list',
                        'GET list-salaries/<id>' => 'list-salaries',
                        //'GET view-salary/<id>' => 'view-salary',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS list-salaries/<id>' => 'options',
                        'OPTIONS view-salary/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],

                [ // StoryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/story',
                    'pluralize' => false,
                    'patterns' => [
                        'GET list' => 'list',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS list' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],

                [ // CandidateWorkHistoryController
                    'class' => 'yii\rest\UrlRule',
                    'pluralize' => false,
                    'controller' => 'v1/candidate-work-history',
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // NoteController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/note',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
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
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // RequestController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // ExpenseController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/expense',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
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
