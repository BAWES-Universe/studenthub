<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=mysql;dbname=studenthub', // Docker mysql service name for host
            'username' => 'studenthubuser',
            'password' => '12345',
            'charset' => 'utf8mb4',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'xero' => [
            'class' => 'common\components\Xero',
            //sandbox web app
            'clientId' => '392C9A9B3D5F408689B18A26E8FF41F5',
            "clientSecret" => "9PlW56cve8wkjPgxvvt3kG2ng3vWhLzH7yMMxADLkYa0q40Z",
            //custom connection
            //'clientId' => 'CF8C4521B478EB2654D4317AEF2D9',
            //"clientSecret" => "hUv5IzcGOkZv0J6D185FJw73tNUDrHR8vswI2sERUKXC7Jgm",
            "xeroTenantId" => "c9895946-8dcc-4670-87be-ec1cca21c6d4"
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
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
            "sqsRagion" => "eu-west-2",
            "sqsKey" => "AKIAWMITDJRKXNWDOBNJ",
            "sqsSecret" => "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",
            "sqsQueue" => "438663597141/StudenthubDev"
        ],
        'mediaConvert' => [
            'class' => 'common\components\MediaConvert',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'region' => 'eu-west-2', // based in London
            'endpoint' => 'https://ey3xqwxpb.mediaconvert.eu-west-2.amazonaws.com',
            'role' => 'arn:aws:iam::438663597141:role/MediaConvertPermissions',
            'jobQueue' =>  "arn:aws:mediaconvert:eu-west-2:438663597141:queues/Default"
        ],
        'resourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'region' => 'eu-west-2', // Bucket based in London
            'bucket' => 'studenthub-uploads-dev-server',
            /**
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
                        'environment' => 'dev',
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
