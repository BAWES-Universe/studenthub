<?php
return [
    'name' => 'StudentHub Internship Program',
    'timeZone' => 'Asia/Kuwait',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'language' => 'en', // <- here!
    'components' => [
        'temporaryBucketResourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'region' => 'eu-west-2', // Bucket based in London
            'key' => 'AKIAWMITDJRKVN5ODY2X',
            'secret' => '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',
            'bucket' => 'studenthub-public-anyone-can-upload-24hr-expiry'
            /**
             * You can access the Temporary bucket with:
             * https://studenthub-public-anyone-can-upload-24hr-expiry.s3.amazonaws.com/
             * https://studenthub-public-anyone-can-upload-24hr-expiry.s3.amazonaws.com/folderName/fileName.jpg
             */
        ],
        'idExpiryDateExtractor' => [
            'class' => 'common\components\IdExpiryDateExtractor',
            'key' => getenv('AWS_TEXTRACT_ACCESS_KEY_ID') ?: '',
            'secret' => getenv('AWS_TEXTRACT_SECRET_ACCESS_KEY') ?: '',
        ],
        'googleMap' => [
        'class' => 'common\components\GoogleMap',
        'accessKey' => getenv('GOOGLE_MAPS_API_KEY'),
        ],
        'reCaptcha' => [
            'class' => 'common\components\ReCaptcha',
            'secretKey' => getenv('RECAPTCHA_SECRET_KEY') ?: '',
        ],
        'jwt' => [
            'class' => 'common\components\JWT'
        ],
        'smsComponent' => [
            'class' => 'common\components\SMSComponent'
        ],
        'jira' => [
            'class' => 'common\components\JiraComponent',
            'jiraUrl' => getenv('JIRA_URL') ?: '',
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
            'accessKey' => getenv('IPINFO_ACCESS_TOKEN') ?: '',
        ],
        'cloudinaryManager' => [
            'class' => 'common\components\CloudinaryManager',
            'cloud_name' => 'studenthub',
            'api_key' => '251218449868375',
            'api_secret' => 'FILAex7q93GUB-q1bEe1pAKOIvY'
            /**
             * You can access the bucket with:
             * http://res.cloudinary.com/studenthub/
             * http://res.cloudinary.com/studenthub/image/upload/candidate-photo/fileName.jpg
             */ 
        ],
        'formatter' => [
            'currencyCode' => 'KWD',
            'defaultTimeZone' => 'Asia/Kuwait',
        ],
        'slack' => [
            'class' => 'understeam\slack\Client',
            'url' => getenv('SLACK_WEBHOOK_URL') ?: '',
            'username' => 'StudentHub',
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
            'dateAttributeValue' => new yii\db\Expression('NOW()')
        ],
        'httpclient' => [
            'class' =>'yii\httpclient\Client',
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
