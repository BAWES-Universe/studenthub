<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-candidate',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'candidate\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'candidate\modules\v1\Module',
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
            'identityClass' => 'candidate\models\Candidate',
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
                        'POST email-check' => 'email-check',
                        'POST register' => 'signup',
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        'OPTIONS register' => 'options',
                        'OPTIONS email-check' => 'options',
                        'OPTIONS update-password' => 'options',
                    ]
                ],
                [ // AccountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/account',
                    'pluralize' => false,
                    'patterns' => [
                        'GET salary' => 'salary',
                        'GET employer' => 'employer',
                        'POST change-password' => 'change-password',
                        'POST language-pref' => 'language-pref', 
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS salary' => 'options',
                        'OPTIONS employer' => 'options',
                        'OPTIONS change-password' => 'options',
                        'OPTIONS language-pref' => 'options', 
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET work-history' => 'work-history',
                        //'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS work-history' => 'options',
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
            ],
        ],
    ],
    'params' => $params,
];
