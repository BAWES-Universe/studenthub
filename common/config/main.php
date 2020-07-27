<?php
return [
    'name' => 'StudentHub Internship Program',
    'timeZone' => 'Asia/Kuwait',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
//        'resourceManager' => [
//            'class' => 'common\components\S3ResourceManager',
//            'key' => 'AKIAIKZYNH7OERZMXZ2A',
//            'secret' => '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',
//            'bucket' => 'sh-payroll',
//            'region' => 'eu-west-2'
//            /**
//             * You can access the main bucket with:
//             * https://sh-payroll.s3.amazonaws.com/
//             * https://sh-payroll.s3.amazonaws.com/folderName/fileName.jpg
//             */
//        ],
        'temporaryBucketResourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'region' => 'eu-west-2', // Bucket based in London
            'key' => 'AKIAJXOMRCDE65WKBPUA',
            'secret' => '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',
            'bucket' => 'studenthub-public-anyone-can-upload-24hr-expiry'
            /**
             * You can access the Temporary bucket with:
             * https://studenthub-public-anyone-can-upload-24hr-expiry.s3.amazonaws.com/
             * https://studenthub-public-anyone-can-upload-24hr-expiry.s3.amazonaws.com/folderName/fileName.jpg
             */
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
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
                    'categories' => ['admin\*', 'candidate\*', 'company\*', 'staff\*', 'common\*', 'console\*'],
                ],
            ],
        ],
    ],
];
