<?php
return [
    'name' => 'Payroll',
    'timeZone' => 'Asia/Kuwait',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'resourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'key' => 'AKIAIKZYNH7OERZMXZ2A',
            'secret' => '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',
            'bucket' => 'sh-payroll',
            'region' => 'eu-west-2'
            /**
             * You can access the main bucket with:
             * https://sh-payroll.s3.amazonaws.com/
             * https://sh-payroll.s3.amazonaws.com/folderName/fileName.jpg
             */
        ],
        'temporaryBucketResourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'key' => 'AKIAIKZYNH7OERZMXZ2A',
            'secret' => '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',
            'bucket' => 'bawes-public',
            'region' => 'eu-west-2'
            /**
             * You can access the Temporary bucket with:
             * https://bawes-public.s3.amazonaws.com/
             * https://bawes-public.s3.amazonaws.com/folderName/fileName.jpg
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
            'username' => 'Payroll',
        ],
        'httpclient' => [
            'class' =>'yii\httpclient\Client',
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
