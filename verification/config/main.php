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
    'id' => 'app-verification',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'verification\controllers',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-verification',
        ],
        'session' => [
            // this is the name of the session cookie used for login on the verification
            'name' => 'advanced-verification',
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
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'view/video/<candidate_uid>' => 'view/video',
                'view/resume/<candidate_uid>' => 'view/resume',
                'view/telephone/<candidate_uid>' => 'view/telephone',
                '<candidate_uid:[A-Za-z0-9\_-]+>' => 'site/index',
            ],
        ],
    ],
    'params' => $params,
];
