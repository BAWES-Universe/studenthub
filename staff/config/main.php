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
            'enableCookieValidation' => false,
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
                        'GET view-company-contact' => 'view-company-contact',
                        'GET is-email-exists' => 'is-email-exists',
                        'GET send-verification-email' => 'resend-verification-email',
                        'GET <id>' => 'view',
                        'GET' => 'list',
                        'POST' => 'create',
                        'PATCH add-to-team' => 'add-to-team',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS view-company-contact' => 'options',
                        'OPTIONS add-to-team' => 'options',
                        'OPTIONS is-email-exists' => 'options',
                        'OPTIONS send-verification-email' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET' => 'list',
                        'GET detail/<id>' => 'view',
                        'GET appreciation-certificate/<id>/<wid>' => 'appreciation-certificate',
                        'GET not-assigned' => 'list-not-assigned',
                        'GET assigned' => 'list-assigned',
                        'GET expired-civil-id' => 'list-expired-civil-id',
                        'GET filter' => 'filter',
                        'GET without-bank' => 'list-without-bank-info',
                        'GET search' => 'search',
                        'GET transfers/<id>' => 'transfers',
                        'GET candidate-resume-pdf/<id>' => 'candidate-resume-pdf',
                        'GET work-history/<id>' => 'work-history',
                        'GET total-to-review' => 'total-to-review',
                        'GET assigned-idle-candidate' => 'assigned-idle-candidates',
                        'POST' => 'create',
                        'PATCH toggle-committed' => 'toggle-committed',
                        'PATCH merge' => 'merge',
                        'PATCH assign/<id>' => 'assign',
                        'PATCH update-hour-rate/<id>' => 'update-candidate-hour-rate',
                        'PATCH job-search-status' => 'job-search-status',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'PATCH <id>' => 'update',
                        'PATCH approve/<id>' => 'approve',
                        'PATCH unapprove/<id>' => 'unapprove',
                        'PATCH expire-card/<id>' => 'expire-candidate-card',
                        'PATCH update-email/<id>' => 'update-candidate-email',
                        'DELETE unassign/<id>' => 'unassign',

                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS toggle-committed' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS merge' => 'options',
                        'OPTIONS filter' => 'options',
                        'OPTIONS detail/<id>' => 'options',
                        'OPTIONS candidate-resume-pdf/<id>' => 'options',
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
                        'OPTIONS unapprove/<id>' => 'options',
                        'OPTIONS update-hour-rate/<id>' => 'options',
                        'OPTIONS expire-card/<id>' => 'options',
                        'OPTIONS appreciation-certificate/<id>/<wid>' => 'options',
                        'OPTIONS list-expired-civil-id' => 'options',
                        'OPTIONS update-email/<id>' => 'options',
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
                        'PATCH update-manager/<id>' => 'update-manager',
                        'DELETE remove-manager/<id>' => 'remove-manager',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS update-manager/<id>' => 'options',
                        'OPTIONS remove-manager/<id>' => 'options',
                    ]
                ],
                [ // CompanyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company',
                    'patterns' => [
                        'GET' => 'list',
                        'GET followups' => 'followups',
                        'GET payroll-email/<id>' => 'payroll-email',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'POST file-create/<id>' => 'create-file',
                        'POST add-followup-note/<id>' => 'add-followup-note',
                        'PATCH <id>' => 'update',
                        'PATCH update-followup/<id>' => 'update-followup',
                        'PATCH update-followup-interval/<id>' => 'update-followup-interval',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS followups' => 'options',
                        'OPTIONS payroll-email/<id>' => 'options',
                        'OPTIONS update-followup/<id>' => 'options',
                        'OPTIONS update-followup-interval/<id>' => 'options',
                        'OPTIONS file-create/<id>' => 'options',
                        'OPTIONS add-followup-note/<id>' => 'options',
                        'OPTIONS change-status/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
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
                        'GET list-candidate-without-card' => 'list-without-card-with-job',
                        // OPTIONS VERBS
                        'OPTIONS list-candidate-ids' => 'options',
                        'OPTIONS list-candidates' => 'options',
                        'OPTIONS generate' => 'options',
                        'OPTIONS list-expired' => 'options',
                        'OPTIONS renew' => 'options',
                        'OPTIONS total-expired' => 'options',
                        'OPTIONS <id>/<token>' => 'options',
                        'OPTIONS list-candidate-without-card' => 'options',
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
                [ // RequestController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request',
                    'patterns' => [
                        'GET' => 'list',
                        'GET active' => 'list-active',
                        'GET is-request-updated/<id>' => 'is-request-updated',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH update-interval/<id>' => 'update-interval',
                        'PATCH update-status/<id>' => 'update-status',
                        'PATCH cancel/<id>' => 'cancel',
                        'PATCH deliver/<id>' => 'deliver',
                        'POST add-activity' => 'add-activity',
                        'PATCH assign/<id>' => 'assign',
                        'PATCH <id>' => 'update',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS is-request-updated/<id>' => 'options',
                        'OPTIONS active' => 'options',
                        'OPTIONS cancel/<id>' => 'options',
                        'OPTIONS deliver/<id>' => 'options',
                        'OPTIONS add-activity' => 'options',
                        'OPTIONS assign/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS update-interval/<id>' => 'options',
                        'OPTIONS update-status/<id>' => 'options',
                    ]
                ],
                [ // BrandController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/brand',
                    'patterns' => [
                        'GET' => 'list',
                        'GET company/<id>' => 'company-brand-list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS company/<id>' => 'options',
                    ]
                ],
                [ // FulltimerController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/fulltimer',
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
                [ // MallController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/mall',
                    'patterns' => [
                        'GET' => 'list',
                        'GET all' => 'list-all',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // InvitationController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/invitation',
                    'patterns' => [
                        'GET' => 'list',
                        'POST' => 'create',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // SuggestionController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/suggestion',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH accept/<id>' => 'accept',
                        'PATCH reject/<id>' => 'reject',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS accept/<id>' => 'options',
                        'OPTIONS reject/<id>' => 'options',
                    ]
                ],
                [ // TransferController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer',
                    'patterns' => [
                        'GET' => 'list',
                        'GET transfer-excel-template/<id>' => 'transfer-excel-template',
                        'GET pdf/<id>' => 'pdf',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'POST create-by-excel' => 'create-by-excel',
                        'PATCH payment-sent/<id>' => 'payment-sent',
                        'PATCH lock/<id>' => 'lock',
                        'PATCH edit-by-excel/<id>' => 'edit-by-excel',
                        'PATCH <id>' => 'edit',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS transfer-excel-template/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS create-by-excel' => 'options',
                        'OPTIONS payment-sent/<id>' => 'options',
                        'OPTIONS lock/<id>' => 'options',
                        'OPTIONS edit-by-excel/<id>' => 'options',
                        'OPTIONS pdf/<id>' => 'options'
                    ]
                ],
                [ // GoogleMapController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/google-map',
                    'pluralize' => false,
                    'patterns' => [
                        'GET place-detail/<place_id>' => 'place-detail',
                        'GET place-predictions' => 'place-predictions',
                        'GET area-by-location' => 'area-by-location',
                        // OPTIONS VERBS
                        'OPTIONS place-detail/<place_id>' => 'options',
                        'OPTIONS place-predictions' => 'options',
                        'OPTIONS area-by-location' => 'options',
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
                [ // StaffController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ]
            ],
        ],
    ],
    'params' => $params,
];
