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
            'enableCookieValidation' => false,
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
                        'GET locate' => 'locate',
                        'POST email-check' => 'email-check',
                        'POST register' => 'signup',
                        'POST request-reset-password' => 'request-reset-password',
                        'POST sms-reset-password' => 'sms-reset-password',
                        'POST is-email-verified' => 'is-email-verified',
                        'POST update-email' => 'update-email',          
                        'POST resend-verification-email' => 'resend-verification-email',
                        'POST login-by-apple' => 'login-by-apple',
                        'POST login-by-google' => 'login-by-google',
                        'POST verify-email' => 'verify-email',
                        'POST login-auth0' => 'login-auth0',
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS name-by-civil-id' => 'options',
                        'OPTIONS login' => 'options',
                        'OPTIONS locate' => 'options',
                        'OPTIONS register' => 'options',
                        'OPTIONS email-check' => 'options',
                        'OPTIONS update-password' => 'options',
                        'OPTIONS request-reset-password' => 'options',
                        'OPTIONS sms-reset-password' => 'options',
                        'OPTIONS login-by-apple' => 'options',
                        'OPTIONS login-by-google' => 'options',
                        'OPTIONS is-email-verified' => 'options',
                        'OPTIONS update-email' => 'options',        
                        'OPTIONS login-auth0' => 'options',
                        'OPTIONS resend-verification-email' => 'options',
                        'OPTIONS verify-email' => 'options'
                    ]
                ],
                [ // AccountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/account',
                    'pluralize' => false,
                    'patterns' => [
                        'GET salary' => 'salary',
                        'GET profile' => 'profile',
                        'GET job-search-status' => 'get-job-search-status',
                        'GET area-by-location' => 'area-by-location',
                        'GET video-status' => 'video-status',
                        'POST current-status' => 'working-status',
                        'POST start-time' => 'start-working-time',
                        'POST stop-time' => 'stop-working-time',
                        'POST video-by-webhook' => 'video-by-webhook',
                        'POST job-search-status' => 'job-search-status',
                        'POST change-password' => 'change-password',
                        'POST update-email' => 'update-email',      
                        'POST language-pref' => 'language-pref', 
                        'POST update-name' => 'update-name',
                        'POST update-name-ar' => 'update-name-ar',
                        'POST update-location' => 'update-location',
                        'POST update-civil-id' => 'update-civil-id',
                        'POST update-nationality' => 'update-nationality',
                        'POST update-university' => 'update-university',
                        'POST update-driving-license' => 'update-driving-license',
                        'POST update-kuwaiti-national' => 'update-kuwaiti-national',
                        'POST update-gender' => 'update-gender',
                        'POST update-objective' => 'update-objective',
                        'POST update-intro' => 'update-intro',
                        'POST update-resume' => 'update-resume',
                        'POST update-birth-date' => 'update-birth-date',
                        'POST profile-photo' => 'profile-photo',
                        'POST video' => 'video',
                        'POST update-skills' => 'update-skills',
                        'POST update-experiences' => 'update-experiences',
                        'POST update-bank-detail' => 'update-bank-detail',
                        'POST update-phone' => 'update-phone',
                        'POST update-civil-photo-back' => 'update-civil-photo-back',
                        'POST update-civil-photo-front' => 'update-civil-photo-front',
                        'POST update-civil-expiry-date' => 'update-civil-expiry-date',
                        'POST update-civil-id-expiry-date' => 'update-civil-id-expiry-date',
                        'POST update-preferred-time' => 'update-preferred-time',
                        'POST update-profile-url' => 'profile-url',
                        'DELETE remove-photo' => 'remove-photo',
                        'DELETE remove-video' => 'remove-video',
                        'DELETE remove-civil-photo-front' => 'remove-civil-photo-front',
                        'DELETE remove-civil-photo-back' => 'remove-civil-photo-back',
                        'DELETE remove-candidate-profile' => 'delete-profile',
                        'POST validate-password' => 'validate-user-password',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS video-status' => 'options',
                        'OPTIONS video-by-webhook' => 'options',
                        'OPTIONS job-search-status' => 'options',
                        'OPTIONS update-civil-photo-back' => 'options',
                        'OPTIONS update-civil-photo-front' => 'options',
                        'OPTIONS update-civil-expiry-date' => 'options',
                        'OPTIONS update-experiences' => 'options',
                        'OPTIONS update-skills' => 'options',
                        'OPTIONS profile' => 'options',
                        'OPTIONS salary' => 'options',
                        'OPTIONS update-email' => 'options', 
                        'OPTIONS change-password' => 'options',
                        'OPTIONS language-pref' => 'options', 
                        'OPTIONS update-name' => 'options',
                        'OPTIONS update-location' => 'options',
                        'OPTIONS area-by-location' => 'options',
                        'OPTIONS update-name-ar' => 'options',
                        'OPTIONS update-civil-id' => 'options',
                        'OPTIONS update-nationality' => 'options',
                        'OPTIONS update-university' => 'options',
                        'OPTIONS update-driving-license' => 'options',
                        'OPTIONS update-objective' => 'options',
                        'OPTIONS update-intro' => 'options',
                        'OPTIONS update-gender' => 'options',
                        'OPTIONS update-resume' => 'options',
                        'OPTIONS update-birth-date' => 'options',
                        'OPTIONS profile-photo' => 'options',
                        'OPTIONS video' => 'options',
                        'OPTIONS remove-photo' => 'options',
                        'OPTIONS remove-video' => 'options',
                        'OPTIONS update-bank-detail' => 'options',
                        'OPTIONS update-phone' => 'options',
                        'OPTIONS remove-civil-photo-back' => 'options',
                        'OPTIONS remove-civil-photo-front' => 'options',
                        'OPTIONS update-kuwaiti-national' => 'options',
                        'OPTIONS update-civil-id-expiry-date' => 'options',
                        'OPTIONS update-preferred-time' => 'options',
                        'OPTIONS start-time' => 'options',
                        'OPTIONS stop-time' => 'options',
                        'OPTIONS current-status' => 'options',
                        'OPTIONS remove-candidate-profile' => 'options',
                        'OPTIONS validate-password' => 'options',
                        'OPTIONS update-profile-url' => 'options',
                    ]
                ],
                [ // GoogleMapController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/google-map',
                    'pluralize' => false,
                    'patterns' => [
                        'GET place-detail/<place_id>' => 'place-detail',
                        'GET place-predictions' => 'place-predictions',
                        // OPTIONS VERBS
                        'OPTIONS place-detail/<place_id>' => 'options',
                        'OPTIONS place-predictions' => 'options'
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET work-history' => 'work-history',
                        'GET appreciation-certificate/<wid>' => 'appreciation-certificate',
                        //'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS work-history' => 'options',
                        'OPTIONS appreciation-certificate/<wid>' => 'options',
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate-working-hour',
                    'patterns' => [
                        'GET date' => 'list-date',
                        'GET hour' => 'list-hour',
                        'GET date/<date>' => 'hours-detail',
                        // OPTIONS VERBS
                        'OPTIONS date' => 'options',
                        'OPTIONS hour' => 'options',
                        'OPTIONS date/<date>' => 'options',
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
                [ // InvitationController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/invitation',
                    'patterns' => [
                        'GET' => 'list',
                        'GET log/<id>' => 'log',
                        'GET log-viewed' => 'log-viewed',
                        'GET <id>' => 'view',
                        'PATCH accept/<id>' => 'accept',
                        'PATCH reject/<id>' => 'reject',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS log-viewed' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS accept/<id>' => 'options',
                        'OPTIONS reject/<id>' => 'options',
                    ]
                ],
                [ // CountryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/country',
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // UniversityController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/university',
                    'patterns' => [
                        'GET' => 'list',
                        'POST' => 'create',
                        'POST is-exists' => 'is-exists',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS is-exists' => 'options',
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
