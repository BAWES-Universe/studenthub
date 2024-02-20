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
                        'POST login-auth0' => 'login-auth0',
                        'POST login-by-google' => 'login-by-google',
                        'PATCH update-password' => 'update-password',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        'OPTIONS login-auth0' => 'options',
                        'OPTIONS login-by-google' => 'options',
                        'OPTIONS update-password' => 'options',
                    ]
                ],
                [ // AccountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/account',
                    'pluralize' => false,
                    'patterns' => [
                        'POST update-password' => 'update-password',
                        'POST validate-password' => 'validate-user-password',
                        'PATCH' => "update",
                        'GET' => 'account',
                        // OPTIONS VERBS
                        'OPTIONS update-password' => 'options',
                        'OPTIONS validate-password' => 'options',
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // DailyStandupController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/daily-standup',
                    'pluralize' => false,
                    'patterns' => [
                        'GET work-session' => 'list-work-session',
                        'POST start-session' => 'start-session',
                        'PATCH end-session' => 'end-session',
                        'POST leave-request' => 'leave-request',
                        'POST answer/<question_uuid>' => 'answer',
                        'GET question' => 'question',
                        'GET session' => 'session',
                        'GET absences' => 'absences',
                        // OPTIONS VERBS
                        'OPTIONS absences' => 'options',
                        'OPTIONS question' => 'options',
                        'OPTIONS session' => 'options',
                        'OPTIONS start-session' => 'options',
                        'OPTIONS end-session' => 'options',
                        'OPTIONS leave-request' => 'options',
                        'OPTIONS work-session' => 'options',
                        'OPTIONS answer/<question_uuid>' => 'options'
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
                        'PATCH mark-email-verified' => 'mark-email-verified',
                        'PATCH <id>' => 'update',
                        'PATCH remove-from-team/<id>' => 'remove-from-team',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS mark-email-verified' => 'options',
                        'OPTIONS view-company-contact' => 'options',
                        'OPTIONS add-to-team' => 'options',
                        'OPTIONS is-email-exists' => 'options',
                        'OPTIONS send-verification-email' => 'options',
                        'OPTIONS remove-from-team/<id>' =>'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET' => 'list',
                        'GET assigned-history-list' => 'assigned-history-list',
                        'GET export-assigned-history' => 'export-assigned-history',
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
                        'GET candidate-warnings/<id>' => 'candidate-warnings',
                        'GET total-to-review' => 'total-to-review',
                        'GET assigned-idle-candidate' => 'assigned-idle-candidates',
                        'GET export-candidate' => 'export-candidate-data',
                        'POST add-tag/<id>' => 'add-tag',
                        'POST warn-candidate/<id>' => 'warn-candidate',
                        'PATCH update-warning/<id>' => 'update-warning',
                        'POST' => 'create',
                        'PATCH toggle-committed' => 'toggle-committed',
                        'PATCH merge' => 'merge',
                        'PATCH update-tags/<id>' => 'update-tags',
                        'PATCH assign/<id>' => 'assign',
                        'PATCH update-hour-rate/<id>' => 'update-candidate-hour-rate',
                        'PATCH update-civil-expiry/<id>' => 'update-candidate-civil-expiry',
                        'PATCH job-search-status' => 'job-search-status',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'PATCH <id>' => 'update',
                        'PATCH approve/<id>' => 'approve',
                        'PATCH unapprove/<id>' => 'unapprove',
                        'PATCH expire-card/<id>' => 'expire-candidate-card',
                        'PATCH update-email/<id>' => 'update-candidate-email',
                        'DELETE unassign/<id>' => 'unassign',
                        'DELETE mark-duplicate/<id>' => 'mark-duplicate',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS mark-duplicate/<id>' => 'options',
                        'OPTIONS update-warning/<id>' => 'options',
                        'OPTIONS warn-candidate/<id>' => 'options',
                        'OPTIONS toggle-committed' => 'options',
                        'OPTIONS add-tag/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS update-tags/<id>' => 'options',
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
                        'OPTIONS candidate-warnings/<id>' => 'options',
                        'OPTIONS list-expired-civil-id' => 'options',
                        'OPTIONS update-email/<id>' => 'options',
                        'OPTIONS update-civil-expiry/<id>' => 'options',
                        'OPTIONS export-candidate' => 'options',
                        'OPTIONS assigned-history-list' => 'options',
                        'OPTIONS export-assigned-history' => 'options',
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
                [ // CurrencyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/currency',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // CompanyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company',
                    'patterns' => [
                        'GET' => 'list',
                        'GET assigned-list' => 'assigned-list',
                        'GET followups' => 'followups',
                        'GET payroll-email/<id>' => 'payroll-email',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'POST file-create/<id>' => 'create-file',
                        'POST add-followup-note/<id>' => 'add-followup-note',
                        'PATCH update-followup/<id>' => 'update-followup',
                        'PATCH update-followup-interval/<id>' => 'update-followup-interval',
                        'PATCH change-status/<id>' => 'change-status',
                        'PATCH <id>' => 'update',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS followups' => 'options',
                        'OPTIONS assigned-list' => 'options',
                        'OPTIONS payroll-email/<id>' => 'options',
                        'OPTIONS update-followup/<id>' => 'options',
                        'OPTIONS update-followup-interval/<id>' => 'options',
                        'OPTIONS file-create/<id>' => 'options',
                        'OPTIONS add-followup-note/<id>' => 'options',
                        'OPTIONS change-status/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // EmailCampaignController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/email-campaign',
                    'patterns' => [
                        'GET' => 'list',
                        'POST status-list' => 'status-list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'PATCH run/<id>' => 'run',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS run/<id>' => 'options',
                    ]
                ],
                [ // CompanyRequestController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company-request',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST accept/<id>' => 'approve',
                        'POST approve/<id>' => 'approve',
                        'POST reject/<id>' => 'reject',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS reject/<id>' => 'options',
                        'OPTIONS approve/<id>' => 'options',
                        'OPTIONS accept/<id>' => 'options'
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
                [ // TagController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/tag',
                    'patterns' => [
                        'GET' => 'list',
                        'GET list' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS list' => 'options',
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
                [ // WebhookController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/webhook',
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
                [ // JiraController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/jira',
                    'pluralize' => false,
                    'patterns' => [
                        'GET users' => 'users',
                        'GET issues' => 'issues',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS users' => 'options',
                        'OPTIONS issues' => 'options',
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
                [ // StoryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/story',
                    'pluralize' => false,
                    'patterns' => [
                        'GET list' => 'list',
                        'GET active-story' => 'active-story',
                        'GET all-old-stories' => 'all-old-stories',
                        'GET is-story-updated/<id>' => 'is-story-updated',
                        'GET <id>' => 'view',
                        'PATCH assign/<id>' => 'assign',
                        'POST change-story-status' => 'change-story-status',
                        'POST create-story' => 'create-story',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS list' => 'options',
                        'OPTIONS active-story' => 'options',
                        'OPTIONS all-old-stories' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS assign/<id>' => 'options',
                        'OPTIONS change-story-status>' => 'options',
                        'OPTIONS is-story-updated/<id>' => 'options',
                        'OPTIONS create-story' => 'options',
                    ]
                ],
                [ // RequestController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request',
                    'patterns' => [
                        'GET' => 'list',
                        'GET active' => 'list-active',
                        'GET pending-request' => 'pending-request',
                        'GET list-checklist' => 'list-checklist',
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
                        'OPTIONS pending-request' => 'list-checklist',
                        'OPTIONS list-checklist' => 'list-checklist',
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
                        'GET is-already-invited' => 'is-already-invited',
                        'PATCH resend/<id>' => 'resend',
                        'POST' => 'create',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS resend/<id>' => 'options',
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
                        'GET candidates' => 'list-candidate',
                        'GET export-companies-transfer' => 'export-companies-transfer',
                        'GET transfer-excel-template/<id>' => 'transfer-excel-template',
                        'GET pdf/<id>' => 'pdf',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'POST create-by-excel' => 'create-by-excel',
                        'PATCH payment-sent/<id>' => 'payment-sent',
                        'PATCH lock/<id>' => 'lock',
                        'PATCH cancel/<id>' => 'cancel',
                        'PATCH edit-by-excel/<id>' => 'edit-by-excel',
                        'PATCH <id>' => 'edit',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS candidates' => 'options',
                        'OPTIONS transfer-excel-template/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS create-by-excel' => 'options',
                        'OPTIONS payment-sent/<id>' => 'options',
                        'OPTIONS lock/<id>' => 'options',
                        'OPTIONS edit-by-excel/<id>' => 'options',
                        'OPTIONS pdf/<id>' => 'options',
                        'OPTIONS cancel/<id>' => 'options',
                        'OPTIONS export-companies-transfer' => 'options'
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
                ],
                [ // CandidateWorkingHourController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate-working-hour',
                    'patterns' => [
                        'GET date' => 'list-date',
                        'GET hour' => 'list-hour',
                        'GET date/<date>/<candidateId>' => 'hours-detail',
                        // OPTIONS VERBS
                        'OPTIONS date' => 'options',
                        'OPTIONS hour' => 'options',
                        'OPTIONS date/<date>/<candidateId>' => 'options',
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
                [ // CandidateEvaluationController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate-evaluation',
                    'pluralize' => false,
                    'patterns' => [
                        'GET question-by-dept/<id>' => 'list-question-by-dept',
                        'GET list-report/<id>' => 'list-report',
                        'GET report/<id>' => 'view-report',
                        'GET pdf/<id>' => 'pdf',
                        'POST' => 'create',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS question-by-dept/<id>' => 'options',
                        'OPTIONS list-report/<id>' => 'options',
                        'OPTIONS report/<id>' => 'options',
                        'OPTIONS pdf/<id>' => 'options',
                    ]
                ],
                [ // StaffExpensesController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff-expenses',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
//                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],

                [ // staffLeaveController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff-leave',
                    'pluralize' => false,
                    'patterns' => [
                        'GET ' => 'list',
                        'POST' => 'create',
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
