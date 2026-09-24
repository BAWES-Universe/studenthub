<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=studenthub',
            'username' => 'root',
            'password' => 'studenthub',
            'charset' => 'utf8',
        ],
        'walletDb' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=wallet',//todo: replace with wallet db from sandbox
            'username' => 'root',
            'password' => 'studenthub',
            'charset' => 'utf8',
        ],
        'walletManager' => [
            'class' => 'common\components\WalletManager',
            'apiKey' => 'QSw2ByGUITXFNjJVNNjyzxdbvYP9rXbG',
            'apiEndpoint' => 'https://webhook.dev.wallet.bawes.net/v1',
            'companyWalletUserID' => 'user_fcac8a5f-52a2-11ed-a68e-d85ed3a264df'
        ],
        'yeaster' => [
            'class' => 'common\components\Yeaster',
            "apiEndpoint" => "http://localhost:3001"
        ],
        'xero' => [
            'class' => 'common\components\Xero',
            //sandbox web app
            'clientId' => '392C9A9B3D5F408689B18A26E8FF41F5',
            "clientSecret" => getenv('XERO_CLIENT_SECRET') ?: null,
            //custom connection
            //'clientId' => 'CF8C4521B478EB2654D4317AEF2D9',
            "xeroTenantId" => "c9895946-8dcc-4670-87be-ec1cca21c6d4"
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'cache' => [
            //'class' => 'yii\redis\Cache',
            'class' => 'yii\caching\FileCache',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'scheme' => 'smtp',
                'host' => 'email-smtp.eu-west-1.amazonaws.com',
                'username' => 'AKIAWMITDJRKSH3JXFI4',//AKIAWMITDJRKVNB2AFUL
                'password' => 'd5QvU/BEagVVlKAfVjr6Nxpf2xCJyRZpmnG69YGU',// 'BFXl6illZPE3NP5EQrVNbCO+gMBCopuIi/uy5nwCsUZ6',
                'port' => 587,
                //   'dsn' => 'smtp://AKIAWMITDJRKVNB2AFUL:BFXl6illZPE3NP5EQrVNbCO+gMBCopuIi/uy5nwCsUZ6@email-smtp.eu-west-1.amazonaws.com:587',
            ],/*
            'transport' => [
                'scheme' => 'smtp',
                'host' => 'smtp.elasticemail.com',
                'username' => 'contact@studenthub.co',
                'password' => 'B53B9967191B1466BA30B027F95A726ECE49',
                'port' => 2525,
                'encryption' => 'tls'
            ],*/
        ],
        /*
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'email-smtp.eu-west-1.amazonaws.com',
                'username' => 'AKIAWMITDJRKVNB2AFUL',//AKIAWMITDJRKTH5HBB2O //AKIAWMITDJRKTQGXUQT3
                'password' => 'BFXl6illZPE3NP5EQrVNbCO+gMBCopuIi/uy5nwCsUZ6',//BKyPcINpZJsEVnUrMGymff27eaIztgNwSWN7xI2960eJ //GDkiUbOkIxx4qpd0fcksh//0qKvAITbj4PCywBjh
                'port' => '587',
                'encryption' => 'tls',
                /*
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.sendgrid.net',
                'username' => 'apikey',
                'password' => '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',//WeLoveSHTrainingProg!121',
                'port' => '587',
                'encryption' => 'tls',*
            ],
        ],*/
        'eventManager' => [
            'class' => 'common\components\EventManager',
            "sqsRagion" => "eu-west-2",
            "sqsKey" => "",
            "sqsSecret" => "",
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

                        // Disable notifications for malicious errors from 3rd party
                        /*'send_callback' => function($data) {
                            // Error Types to Ignore
                            $ignore_types = [
                                'yii\web\NotFoundHttpException',
                                'Page not found.'
                            ];

                            if (isset($data['exception']) &&
                                (in_array($data['exception']['values'][0]['type'], $ignore_types) ||
                                in_array($data['exception']['values'][0]['value'], $ignore_types))
                            ){
                                return false;
                            }
                        },*/
                    ],
                    'context' => true // Write the context information. The default is true.
                ],
            ],
        ],
    ],
];
