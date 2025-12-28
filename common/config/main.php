<?php
return [
    'name' => 'StudentHub Internship Program',
    'timeZone' => 'Asia/Kuwait',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'language' => 'en',
    'components' => [
        'temporaryBucketResourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'region' => getenv('AWS_TEMP_BUCKET_REGION') ?: 'eu-west-2',
            'key' => getenv('AWS_TEMP_BUCKET_KEY') ?: '',
            'secret' => getenv('AWS_TEMP_BUCKET_SECRET') ?: '',
            'bucket' => getenv('AWS_TEMP_BUCKET_NAME') ?: 'studenthub-public-anyone-can-upload-24hr-expiry',
        ],
        'idExpiryDateExtractor' => [
            'class' => 'common\components\IdExpiryDateExtractor',
            'key' => getenv('AWS_ID_EXTRACTOR_KEY') ?: '',
            'secret' => getenv('AWS_ID_EXTRACTOR_SECRET') ?: '',
        ],
        'googleMap' => [
            'class' => 'common\components\GoogleMap',
            'accessKey' => getenv('GOOGLE_MAPS_API_KEY') ?: '',
        ],
        'reCaptcha' => [
            'class' => 'common\components\ReCaptcha',
            'secretKey' => getenv('RECAPTCHA_SECRET_KEY') ?: '',
        ],
        'jwt' => [
            'class' => 'common\components\JWT',
        ],
        'smsComponent' => [
            'class' => 'common\components\SMSComponent',
        ],
        'jira' => [
            'class' => 'common\components\JiraComponent',
            'jiraUrl' => getenv('JIRA_URL') ?: 'https://bawes-studenthub.atlassian.net',
            'email' => getenv('JIRA_EMAIL') ?: '',
            'apiToken' => getenv('JIRA_API_TOKEN') ?: '',
        ],
        'algolia' => [
            'class' => 'common\components\Algolia',
            'appId' => getenv('ALGOLIA_APP_ID') ?: '',
            'apiKey' => getenv('ALGOLIA_API_KEY') ?: '',
        ],
        'ipstack' => [
            'class' => 'common\components\Ipstack',
            'accessKey' => getenv('IPSTACK_ACCESS_KEY') ?: '',
        ],
        'cloudinaryManager' => [
            'class' => 'common\components\CloudinaryManager',
            'cloud_name' => getenv('CLOUDINARY_CLOUD_NAME') ?: 'studenthub',
            'api_key' => getenv('CLOUDINARY_API_KEY') ?: '',
            'api_secret' => getenv('CLOUDINARY_API_SECRET') ?: '',
        ],
        'formatter' => [
            'currencyCode' => 'KWD',
            'defaultTimeZone' => 'Asia/Kuwait',
        ],
        'slack' => [
            'class' => 'understeam\slack\Client',
            'url' => getenv('SLACK_WEBHOOK_URL') ?: '',
            'username' => getenv('SLACK_USERNAME') ?: 'StudentHub',
        ],
        'auth0' => [
            'class' => 'common\components\Auth0',
        ],
        'config' => [
            'class' => 'common\components\Config',
        ],
        'balanceManager' => [
            'class' => 'yii2tech\balance\ManagerDb',
            'db' => 'walletDb',
            'accountTable' => '{{%balance_account}}',
            'transactionTable' => '{{%balance_transaction}}',
            'autoCreateAccount' => 'true',
            'accountLinkAttribute' => 'account_uuid',
            'accountBalanceAttribute' => 'balance',
            'amountAttribute' => 'amount',
            'dataAttribute' => 'data',
            'dateAttribute' => 'created_at',
            'dateAttributeValue' => new yii\db\Expression('NOW()'),
        ],
        'httpclient' => [
            'class' => 'yii\httpclient\Client',
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                    'sourceLanguage' => 'en',
                ],
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                    'sourceLanguage' => 'en',
                ],
                'candidate' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                    'sourceLanguage' => 'en',
                ],
                'yii' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@common/messages',
                    'sourceLanguage' => 'en',
                ],
            ],
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'common\components\SlackLogger',
                    'logVars' => [],
                    'levels' => ['info', 'error', 'warning'],
                    'categories' => ['admin\*', 'candidate\*', 'company\*', 'manager\*', 'staff\*', 'common\*', 'console\*'],
                ],
            ],
        ],
    ],
];
