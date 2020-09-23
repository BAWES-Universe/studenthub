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
                        'POST email-check' => 'email-check',
                        'POST register' => 'signup',
                        'POST name-by-civil-id' => 'name-by-civil-id',
                        'POST request-reset-password' => 'request-reset-password',
                        'POST is-email-verified' => 'is-email-verified',
                        'POST update-email' => 'update-email',          
                        'POST resend-verification-email' => 'resend-verification-email',
                        'POST verify-email' => 'verify-email',
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS name-by-civil-id' => 'options',
                        'OPTIONS login' => 'options',
                        'OPTIONS register' => 'options',
                        'OPTIONS email-check' => 'options',
                        'OPTIONS update-password' => 'options',
                        'OPTIONS request-reset-password' => 'options',
                        'OPTIONS is-email-verified' => 'options',
                        'OPTIONS update-email' => 'options',        
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
                        'POST update-gender' => 'update-gender',
                        'POST update-objective' => 'update-objective',
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
                        'DELETE remove-photo' => 'remove-photo',
                        'DELETE remove-video' => 'remove-video',
                        'DELETE remove-civil-photo-front' => 'remove-civil-photo-front',
                        'DELETE remove-civil-photo-back' => 'remove-civil-photo-back',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
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
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
            ],
        ],
    ],
    'params' => $params,
];
