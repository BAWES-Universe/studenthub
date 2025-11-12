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
    'id' => 'app-manager',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'manager\modules\v1\Module\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'manager\modules\v1\Module',
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
            'identityClass' => 'common\models\StoreManager',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null
        ],
       /* 'companyManager' => [ //Component for agent to manage Employers
            'class' => 'manager\components\CompanyManager',
        ],
        'storeManager' => [ //Component for agent to manage stores
            'class' => 'manager\components\StoreManager',
        ],*/
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
                [ // StoreController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/store',
                    'patterns' => [
                        'GET view' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS view' => 'options',
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
                        'POST update-email' => 'update-email',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS update' => 'options',
                        'OPTIONS view' => 'options',
                        'OPTIONS change-password' => 'options',
                        'OPTIONS update-email' => 'options',
                    ]
                ],
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
            ],
        ],
    ],
    'params' => $params,
];
