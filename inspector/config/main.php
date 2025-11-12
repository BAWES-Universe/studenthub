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
    'id' => 'app-inspector',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'inspector\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'inspector\modules\v1\Module',
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
                [ // PingController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/ping',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'test',
                        'HEAD' => 'test',
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
                        'POST login-two-step' => 'login-two-step',
                        'POST login-by-key' => "login-by-key",
                        'POST request-reset-password' => 'request-reset-password',
                        'PATCH update-password' => 'update-password',
                        'PATCH set-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        'OPTIONS login-two-step' => 'options',
                        'OPTIONS login-by-key' => 'options',
                        'OPTIONS request-reset-password' => 'options',
                        'OPTIONS update-password' => 'options',
                        'OPTIONS set-password' => 'options',
                    ]
                ],     
                [ // AccountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/account',
                    'pluralize' => false,
                    'patterns' => [
                        'POST update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS update-password' => 'options',
                    ]
                ],
            ],
        ],
    ],
    'params' => $params,
];
