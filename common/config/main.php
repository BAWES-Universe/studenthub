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
            'key' => 'AKIAWMITDJRK7MGFFZMN',
            'secret' => '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'
        ],
        'googleMap' => [
        'class' => 'common\components\GoogleMap',
        'accessKey' => getenv('GOOGLE_MAPS_API_KEY'),
        ],
        'reCaptcha' => [
            'class' => 'common\components\ReCaptcha',
            'secretKey' => "6Lei9R4pAAAAAD5-OIUbCZeMQ00saNLKNuU62b4v"
        ],
        'jwt' => [
            'class' => 'common\components\JWT'
        ],
        'smsComponent' => [
            'class' => 'common\components\SMSComponent'
        ],
        'jira' => [
            'class' => 'common\components\JiraComponent',
            'jiraUrl' => 'https://bawes-studenthub.atlassian.net',
            'email' => 'kk@bawes.net',
            'apiToken' => 'eYVHMtAi16zN56M2PS3gB8AB'
        ],
        'algolia' => [
            'class' => 'common\components\Algolia',
            'appId' => 'VQF0F2SG4Y',
            'apiKey' => 'bce91c65c212d2bb20c079eb15c2283b', 
           // 'publicKey' => '381f91f1c08f4d2788a6821cad1ccbbb'
        ],
        'ipstack' => [
            'class' => 'common\components\Ipstack',
            //'accessKey' => 'fac3c2117d877e078e3e8fa7839d8204'
            'accessKey' => '911bdd76f42e7f'
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
            'url' => 'https://hooks.slack.com/services/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',
            'username' => 'StudentHub',
        ],
        'auth0' => [
            'class' => 'common\components\Auth0',
        ],
        'authentik' => [
            'class' => 'common\components\Authentik',
            'issuer' => getenv('AUTHENTIK_ISSUER_URL') ?: 'https://auth.bawes.net',
            'clientId' => getenv('AUTHENTIK_CLIENT_ID') ?: '',
            'clientSecret' => getenv('AUTHENTIK_CLIENT_SECRET') ?: '',
            'redirectUri' => getenv('AUTHENTIK_REDIRECT_URI') ?: '',
            'jwksUrl' => getenv('AUTHENTIK_JWKS_URL') ?: '',
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
