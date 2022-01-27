<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=payroll',
            'username' => 'root',
            'password' => 'saoud1607',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'urlManagerStaff' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => 'https://staff.api.dev.studenthub.co/v1',
        ],
        'urlManagerCandidate' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => 'https://student.api.dev.studenthub.co/v1',
        ],
        'urlManagerVerification' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => 'https://v.dev.studenthub.co/'
        ],
        'eventManager' => [
            'class' => 'common\components\EventManager',
            'key' => 'ac62dbe81767f8871f754c7bdf6669d6'
        ],
        'mediaConvert' => [
            'class' => 'common\components\MediaConvert',
            'region' => 'eu-west-2', // based in London
            'endpoint' => 'https://ey3xqwxpb.mediaconvert.eu-west-2.amazonaws.com',
            'key' => 'AKIAWMITDJRKWKGYOFLT',
            'secret' => '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',
            'role' => 'arn:aws:iam::438663597141:role/MediaConvertPermissions',
            'jobQueue' =>  "arn:aws:mediaconvert:eu-west-2:438663597141:queues/Default"
        ],
        'resourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'region' => 'eu-west-2', // Bucket based in London
            'key' => 'AKIAJXOMRCDE65WKBPUA',
            'secret' => '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',
            'bucket' => 'studenthub-uploads-dev-server',
            /**
             * For Local Development, we access using key and secret
             * For Dev and Production servers, access is via server embedded IAM roles so no key/secret required
             *
             * You can access the bucket with:
             * https://studenthub-uploads-dev-server.s3.amazonaws.com/
             * https://studenthub-uploads-dev-server.s3.amazonaws.com/folderName/fileName.jpg
             */
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => 'https://6cbd2100e1ff41e7875352655ffbf50d:e18336b09d864b29aa12aca3fbc6706c@sentry.io/168200',
                    'levels' => ['error', 'warning'],
                    'except' => [
                        'yii\web\BadRequestHttpException',
                        'yii\web\UnauthorizedHttpException',
                        'yii\web\NotFoundHttpException',
                        'yii\web\HttpException:400',
                        'yii\web\HttpException:401',
                        'yii\web\HttpException:404',
                    ],
                    'clientOptions' => [
                        //which environment are we running this on?
                        'environment' => 'dev-server',
                    ],
                    'context' => true // Write the context information. The default is true.
                ],
                [
                    'class' => 'common\components\SlackLogger',
                    'logVars' => [],
                    'levels' => ['info', 'warning'],
                    'categories' => ['admin\*', 'candidate\*', 'company\*', 'staff\*', 'remail\*', 'common\*', 'console\*'],
                ],
            ],
        ],
    ],
];
