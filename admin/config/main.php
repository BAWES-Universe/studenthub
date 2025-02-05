<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-admin',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'admin\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'v1' => [
            'class' => 'admin\modules\v1\Module',
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
            'identityClass' => 'common\models\Admin',
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
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // XeroController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/xero',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'list',
                        'GET sync' => 'sync',
                        'GET download' => 'download',
                        'GET history' => 'history',
                        'GET auth' => 'auth',
                        'GET callback' => 'callback',
                        'GET <id>' => 'view',
                        'POST callback' => 'callback',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS sync' => 'options',
                        'OPTIONS download' => 'options',
                        'OPTIONS history' => 'options',
                        'OPTIONS <id>' => 'options',
                     ]
                ],
                [ // AuthController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/auth',
                    'pluralize' => false,
                    'patterns' => [
                        'GET login' => 'login',
                        "POST login-two-step" => "login-two-step",
                        'POST login-auth0' => 'login-auth0',
                        'POST login-by-key' => 'login-by-key',
                        'POST login-by-google' => 'login-by-google',
                        // OPTIONS VERBS
                        'OPTIONS login' => 'options',
                        "OPTIONS login-two-step" => "options",
                        'OPTIONS login-auth0' => 'options',
                        'OPTIONS login-by-google' => 'options',
                        'OPTIONS login-by-key' => 'options',
                    ]
                ],
                [ // StatisticController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/statistic',
                    'patterns' => [
                        'GET' => 'list',
                        'GET clear-cache' => "clear-cache",
                        'GET transfer' => 'transfer',
                        "GET revenue" => "revenue",
                        "GET invitation-graph-data" => "invitation-graph-data",
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS clear-cache' => 'options',
                        'OPTIONS transfer' => 'options',
                        "OPTIONS revenue" => "options",
                        "OPTIONS invitation-graph-data" => "options",
                    ]
                ],
                [ // EventController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/event',
                    'patterns' => [
                        'POST' => 'import-excel',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // SettingController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/setting',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'list',
                        'PATCH' => 'update',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // StaffController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff',
                    'patterns' => [
                        'GET list-salaries/<id>' => 'list-salaries',
                        'GET list-companies/<id>' => 'list-companies',
                        'GET view-salary/<id>' => 'view-salary',
                        'GET <id>' => 'view',
                        'GET' => 'list',
                        'POST login/<id>' => 'login',
                        'POST import-salary' => 'import-salary',
                        'PATCH status-change/<id>' => 'status',
                        'PATCH recover-account/<id>' => 'recover-account',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS status-change/<id>' => 'options',
                        'OPTIONS list-salaries/<id>' => 'options',
                        'OPTIONS view-salary/<id>' => 'options',
                        'OPTIONS login/<id>' => 'options',
                        'OPTIONS import-salary' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                        'OPTIONS recover-account/<id>' => 'options',
                        'OPTIONS list-companies/<id>' => 'options',
                    ]
                ],
                [ // AdminController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/admin',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'PATCH status-change/<id>' => 'status',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                        'OPTIONS status-change/<id>' => 'options',
                    ]
                ],
                [ // InspectorController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/inspector',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                    ]
                ],
                [ // CompanyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company',
                    'patterns' => [
                        'GET' => 'list',
                        'GET download-list-excel' => 'download-list-excel',
                        "GET download-candidates-excel/<id>" => "download-candidates-excel",
                        'GET followups' => 'followups',
                        'GET sub-companies/<id>' => 'sub-companies',
                        'GET year-report' => 'year-report',
                        'GET <id>' => 'view',
                        'POST login/<id>' => 'login',
                        'POST' => 'create',
                        'POST file-create/<id>' => 'create-file',
                        'PATCH file-update/<id>' => 'update-file',
                        'PATCH change-status/<id>' => 'change-status',
                        'PATCH update-followup/<id>' => 'update-followup',
                        'PATCH update-staff/<id>' => 'update-staff',
                        'PATCH update-followup-interval/<id>' => 'update-followup-interval',
                        'PATCH <id>' => 'update',
                        'DELETE remove-file/<id>' => 'delete-file',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        "OPTIONS download-candidates-excel/<id>" => 'options',
                        'OPTIONS login/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS followups' => 'options',
                        'OPTIONS report' => 'options',
                        'OPTIONS sub-companies/<id>' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                        'OPTIONS file-create/<id>' => 'options',
                        'OPTIONS file-update/<id>' => 'options',
                        'OPTIONS remove-file/<id>' => 'options',
                        'OPTIONS change-status/<id>' => 'options',
                        'OPTIONS update-staff/<id>' => 'options',
                        'OPTIONS update-followup/<id>' => 'options',
                        'OPTIONS update-followup-interval/<id>' => 'options',
                        'OPTIONS download-list-excel' => 'options',
                    ]
                ],
                [ // StoreController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/store',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST login/<id>' => 'login',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS login/<id>' => 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate',
                    'patterns' => [
                        'GET search' => 'search',
                        'GET report-search' => 'report-search',
                        'GET total-to-review' => 'total-to-review',
                        'POST login/<id>' => 'login',
                        'PATCH approve/<id>' => 'approve',
                        'PATCH restore/<id>' => 'restore',
                        'PATCH reset-password/<id>' => 'reset-password',
                        'GET transfers/<id>' => 'transfers',
                        'GET work-history/<id>' => 'work-history',
                        'GET <id>' => 'view',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS search' => 'options',
                        'OPTIONS report-search' => 'options',
                        'OPTIONS total-to-review' => 'options',
                        'OPTIONS login/<id>' => 'options',
                        'OPTIONS approve/<id>' => 'options',
                        'OPTIONS transfers/<id>' => 'options',
                        'OPTIONS work-history/<id>' => 'options',
                        'OPTIONS restore/<id>' => 'options',
                        'OPTIONS reset-password/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
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
                [ // YeasterController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/yeaster',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'GET download/<id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS download/<id>' => 'options',
                    ]
                ],
                [ // TransferController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer',
                    'patterns' => [
                        'GET suspicious' => 'suspicious-list',
                        'GET' => 'list',
                        'GET text' => 'text',
                        'GET download-payment-advice' => 'download-payment-advice',
                        'GET payable-candidates' => 'payable-candidates',
                        "GET export-google-excel" => "export-google-excel",
                        'GET export-payable-candidates' => 'export-payable-candidates',
                        "GET download-text-payment-advice-for-abk" => "download-text-payment-advice-for-abk",
                        "GET download-payment-advice-for-abk" => "download-payment-advice-for-abk",
                        'GET invoices/<id>' => 'invoices',
                        'GET export/<id>' => 'export',
                        'GET pdf/<id>/<type>' => 'pdf',
                        'GET <id>' => 'view',
                        "POST import-bank-statement-excel" => "import-bank-statement-excel",
                        'POST import-excel' => 'import-excel',
                        'POST import-kfh-excel' => 'import-kfh-excel',
                        "POST import-google-excel" => "import-google-excel",
                        'POST update-transfer-from-file/<id>' => 'update-transfer-from-file',
                        'PATCH payment-received-distributing/<id>' => 'payment-received-distributing',
                        'PATCH unlock/<id>' => 'unlock',
                        'PATCH lock/<id>' => 'lock',
                        'PATCH cancel/<id>' => 'cancel',
                        'PATCH mark-paid-all' => 'mark-paid-all',
                        'PATCH pay-by-wallet/<id>' => 'pay-by-wallet',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        "OPTIONS import-bank-statement-excel" =>  'options',
                        "OPTIONS export-google-excel" => "options",
                        "OPTIONS download-text-payment-advice-for-abk" =>  'options',
                        "OPTIONS download-payment-advice-for-abk" => 'options',
                        'OPTION download-payment-advice' => 'options',
                        'OPTIONS update-transfer-from-file/<id>' => 'options',
                        'OPTIONS payable-candidates' => 'options',
                        'OPTIONS invoices/<id>' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS import-excel' => 'options',
                        'OPTIONS import-kfh-excel' => 'options',
                        "OPTIONS import-google-excel" => 'options',
                        'OPTIONS payment-received-distributing/<id>' => 'options',
                        'OPTIONS unlock/<id>' => 'options',
                        'OPTIONS lock/<id>' => 'options',
                        'OPTIONS mark-paid-all' => 'options',
                        'OPTIONS export-payable-candidates' => 'options',
                        'OPTIONS text' => 'options',
                        'OPTIONS export/<id>' => 'options',
                        'OPTIONS pdf/<id>/<type>' => 'options',
                        'OPTIONS suspicious' => 'options',
                        'OPTIONS cancel/<id>' => 'options',
                        'OPTIONS pay-by-wallet/<id>' => 'options',
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
                        "PATCH replace/<id>" => 'replace',
                        'PATCH unpaid/<id>' => 'unpaid',
                        'PATCH paid/<id>' => 'paid',
                        'PATCH pay-by-wallet/<id>' => 'pay-by-wallet',
                        'PATCH mark-paid-all' => 'mark-paid-all',
                        'PATCH mark-unpaid-all' => 'mark-unpaid-all',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS by-transfer/<id>' => 'options',
                        'OPTIONS by-transfer-file/<id>' => 'options',
                        'OPTIONS unpaid/<id>' => 'options',
                        'OPTIONS paid/<id>' => 'options',
                        "OPTIONS replace/<id>" => 'options',
                        'OPTIONS pay-by-wallet/<id>' => 'options',
                        'OPTIONS mark-paid-all' => 'options',
                        'OPTIONS mark-unpaid-all' => 'options',
                    ]
                ],
                [ // TransferFileController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer-file',
                    'patterns' => [
                        'GET' => 'list', 
                        'GET <id>' => 'view',
                        "PATCH re-schedule/<id>" => "re-schedule",
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        "OPTIONS re-schedule/<id>"=> 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // ExpenseController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/expense',
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
                [ // DailyStandupQuestionController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/daily-standup-question',
                    'patterns' => [
                        'GET' => 'list',
                        'GET work-session' => 'list-work-session',
                        'GET list-answers' => 'list-answers',
                        'GET absences' => 'absences',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS work-session' => 'options',
                    ]
                ],
                [ // WebhookController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/webhook',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST test/<id>' => 'test',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS test/<id>' => 'options',
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
                [ // BankController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/bank',
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
                [ // MailLogController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/mail-log',
                    'patterns' => [
                        'GET' => 'list',
                        'GET stats/<days>' => "stats",
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS stats/<days>' => "options",
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // CurrencyController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/currency',
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
                [ // BlockedIpController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/blocked-ip',
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
                [ // TagController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/tag',
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
                [ // BrandController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/brand',
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
                [ // CampaignController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/campaign',
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
                [ // CompanyContactController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/company-contact',
                    'patterns' => [
                        'GET' => 'list',
                        'GET view-company-contact' => 'view-company-contact',
                        'GET is-email-exists' => 'is-email-exists',
                        'GET <id>' => 'view',
                        'POST login/<id>' => 'login',
                        'POST' => 'create',
                        'PATCH add-to-team' => 'add-to-team',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS view-company-contact' => 'options',
                        'OPTIONS add-to-team' => 'options',
                        'OPTIONS is-email-exists' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS login/<id>' => 'options',
                    ]
                ],
                [
                    //TransferBankAdviceController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/transfer-bank-advice',
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
                [ // RequestChecklistController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/request-checklist',
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
                [ // UniversityController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/university',
                    'patterns' => [
                        'GET' => 'list',
                        'GET download-list-excel' => 'download-list-excel',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS download-list-excel' => 'options',
                    ]
                ],
                [ // DiscountCategoryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/discount-category',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // DiscountController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/discount',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // DegreeController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/degree',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // DegreeGroupController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/degree-group',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options'
                    ]
                ],
                [ // MajorController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/major',
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
                [ // CountryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/country',
                    'patterns' => [
                        'GET' => 'list',
                        'GET download-list-excel' => 'download-list-excel',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH <id>' => 'update',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS download-list-excel' => 'options',
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
                [ // InvitationController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/invitation',
                    'patterns' => [
                        'GET' => 'list',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                    ]
                ],
                [ // SuggestionController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/suggestion',
                    'patterns' => [
                        'GET' => 'list',
                        'PATCH change-status/<id>' => 'change-status',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS change-status/<id>' => 'options',
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
                        'GET <id>' => 'view',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS list' => 'options',
                        'OPTIONS active-story' => 'options',
                        'OPTIONS all-old-stories' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // CandidateController
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
                [ // PermissionSectionController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/permission-section',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'GET user-permission/<type>/<id>' => 'user-permission',
                        'GET sub/<id>' => 'view',
                        'POST' => 'create',
                        'POST sub' => 'create-sub-section',
                        'PATCH <id>' => 'update',
                        'PATCH sub/<id>' => 'update-sub-section',
                        'PATCH set-permission/<id>' => 'set-permission',
                        'DELETE <id>' => 'delete',
                        'DELETE sub/<id>' => 'delete-sub-section',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS user-permission/<type>/<id>' => 'options',
                        'OPTIONS sub/<id>' => 'options',
                        'OPTIONS set-permission/<id>' => 'options'
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
                [ // CandidateController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/fulltimer',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                    ]
                ],
                [ // DailyStandupAnswerController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/daily-standup-answer',
                    'patterns' => [
                        'GET' => 'list',
                        'GET list-inactive' => 'list-inactive',
                        'GET <staffId>/<date>' => 'view-answer',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS list-inactive' => 'options',
                        'OPTIONS <staffId>/<date>' => 'options',
                    ]
                ],
                [ // StaffWorkSessionController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff-work-session',
                    'patterns' => [
                        'GET' => 'list',
                        'GET list-inactive' => 'list-inactive',
                        'GET download-list-excel' => 'download-list-excel',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS list-inactive' => 'options',
                        'OPTIONS download-list-excel' => 'options',
                    ]
                ],
                [ // StaffSalaryController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff-salary',
                    'pluralize' => false,
                    'patterns' => [
                        'GET' => 'list',
                        'POST create-salary' => 'create-salary',
                        'POST add-salary/<id>' => 'add-salary',
                        'PATCH update-salary/<id>' => 'update-salary',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS create-salary' => 'options',
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS add-salary/<id>' => 'options',
                        'OPTIONS update-salary/<id>' => 'options',
                    ]
                ],
                [ // CandidateEvaluationController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/candidate-evaluation',
                    'pluralize' => false,
                    'patterns' => [
                        'GET question' => 'list-question',
                        'GET question/<id>' => 'view-question',
                        'GET list-assigned-question' => 'list-assigned-question',
                        'GET list-candidate-report' => 'list-candidate-report',
                        'GET report/<id>' => 'view-report',
                        'GET pdf/<id>' => 'pdf',
                        'POST create-question' => 'create-question',
                        'PATCH update-question/<id>' => 'update-question',
                        'PUT assign-question/<id>' => 'assign-question',
                        'DELETE question/<id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS question' => 'options',
                        'OPTIONS report/<id>' => 'options',
                        'OPTIONS pdf/<id>' => 'options',
                        'OPTIONS question/<id>' => 'options',
                        'OPTIONS list-assigned-question' => 'options',
                        'OPTIONS list-candidate-report' => 'options',
                        'OPTIONS create-question' => 'options',
                        'OPTIONS update-question/<id>' => 'options',
                        'OPTIONS assign-question/<id>' => 'options',
                    ]
                ],
                [ // StaffExpensesController
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'v1/staff-expenses',
                    'patterns' => [
                        'GET' => 'list',
                        'GET <id>' => 'view',
                        'POST' => 'create',
                        'PATCH change-status/<id>' => 'change-status',
                        'DELETE <id>' => 'delete',
                        // OPTIONS VERBS
                        'OPTIONS' => 'options',
                        'OPTIONS <id>' => 'options',
                        'OPTIONS change-status/<id>' => 'options',
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
                        'PATCH <id>' => 'change-status',
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
