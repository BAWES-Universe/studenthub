<?php
$sentryDsn = getenv('SENTRY_DSN') ?: null;
$sentryEnvironment = getenv('SENTRY_ENVIRONMENT') ?: (defined('YII_ENV') ? YII_ENV : 'production');
$sentryTracesSampleRate = getenv('SENTRY_TRACES_SAMPLE_RATE');
$sentryTracesSampleRate = is_numeric($sentryTracesSampleRate) ? max(0.0, min(1.0, (float) $sentryTracesSampleRate)) : 0.1;

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=mysql.railway.internal;dbname=railway',
            'username' => 'root',
            'password' => 'JImnisvcRDpKLdWpoMECoHHoCbutPhQC',
            'charset' => 'utf8mb4',
            // Enable Caching of Schema to Reduce SQL Queries
            'enableSchemaCache' => true,
            // Duration of schema cache.
            'schemaCacheDuration' => 3600, // 1 hr
            // Name of the cache component used to store schema information
            'schemaCache' => 'cache',
        ],
        'walletDb' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=mysql-5abl.railway.internal;dbname=railway',
            'username' => 'root',
            'password' => 'mECIXVloEolvFJXnDTcuLGUtvbwzoCgS',

            'charset' => 'utf8',
            // Enable Caching of Schema to Reduce SQL Queries
            'enableSchemaCache' => true,
            // Duration of schema cache.
            'schemaCacheDuration' => 3600, // 1 hr
            // Name of the cache component used to store schema information
            'schemaCache' => 'cache',
        ],
        'walletManager' => [
            'class' => 'common\components\WalletManager',
            'apiKey' => 'POAO-BiBxj-Oqp2XOIDZgSDrTYJxOa3M',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'redis.railway.internal',
            'username' => 'default',
            'password' => 'VjCTsdeqMTNwmzBidlzbciDRVceiFXYS',
            'port' => 6379,
            'database' => 0,
        ],/*
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'studenthub-0x1cgp.serverless.euw2.cache.amazonaws.com',
            'port' => 6379,
            'database' => 0,
        ],*/
        'cache' => [
            //'class' => 'yii\redis\Cache',
            'class' => 'yii\caching\FileCache',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'scheme' => 'smtp',
                'host' => getenv('MAIL_HOST') ?: 'smtp.resend.com',
                'username' => getenv('MAIL_USERNAME') ?: 'resend',
                'password' => getenv('MAIL_PASSWORD'),
                'port' => (int)(getenv('MAIL_PORT') ?: 587),
                // 'host' => 'email-smtp.eu-west-1.amazonaws.com',
                // 'username' => 'AKIAWMITDJRKUESNXW5I',
                // 'password' => 'BNLEls4MLvkjiAltRpWLTic7IMwKhggzqRVpHU5C9TFh',
                // 'port' => 587,

                /*
               'host' => 'smtp.elasticemail.com',
               'username' => 'no-reply@mail.studenthub.co',
               'password' => 'FB28388CE97459B250D9A24BBC650AAD2466',
               'port' => 2525,
               'encryption' => 'tls'

               'scheme' => 'smtp',
               'host' => 'email-smtp.eu-west-1.amazonaws.com',
               'username' => 'AKIAWMITDJRKSH3JXFI4',//AKIAWMITDJRKVNB2AFUL
               'password' => 'd5QvU/BEagVVlKAfVjr6Nxpf2xCJyRZpmnG69YGU',// 'BFXl6illZPE3NP5EQrVNbCO+gMBCopuIi/uy5nwCsUZ6',
               'port' => 587,
               //   'dsn' => 'smtp://AKIAWMITDJRKVNB2AFUL:BFXl6illZPE3NP5EQrVNbCO+gMBCopuIi/uy5nwCsUZ6@email-smtp.eu-west-1.amazonaws.com:587',
           */
            ],
        ],/*
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                /*'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.elasticemail.com',
                'username' => 'contact@studenthub.co',
                'password' => 'B53B9967191B1466BA30B027F95A726ECE49',
                'port' => '2525',
                'encryption' => 'tls'*

                'class' => 'Swift_SmtpTransport',
                'host' => 'email-smtp.eu-west-1.amazonaws.com',
                'username' => 'AKIAWMITDJRKVNB2AFUL',//AKIAWMITDJRKTH5HBB2O //AKIAWMITDJRKTQGXUQT3
                'password' => 'BFXl6illZPE3NP5EQrVNbCO+gMBCopuIi/uy5nwCsUZ6',//BKyPcINpZJsEVnUrMGymff27eaIztgNwSWN7xI2960eJ //GDkiUbOkIxx4qpd0fcksh//0qKvAITbj4PCywBjh
                'port' => '587  ',
                'encryption' => 'tls',

                /*'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.eu.mailgun.org',
                'username' => 'postmaster@studenthub.co',
                'password' => '345f8ffa2c7eb8af3c398e53976f67b0-18e06deb-bdad79c2',
                'port' => '587',
                'encryption' => 'tls'
                // 'plugins' => [
                //     [
                //         'class' => 'Openbuildings\Swiftmailer\CssInlinerPlugin',
                //     ],
                // ],
            ],
        ],*/
        /*
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.sendgrid.net',
                'username' => 'apikey',
                'password' => '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',//WeLoveSHTrainingProg!121',
                'port' => '587',
                'encryption' => 'tls',
            ],
        ],*/
        'eventManager' => [
            'class' => 'common\components\EventManager',
            //todo: commenting down as it's slowing down all apis
            //"sqsRagion" => "eu-west-2",
            //"sqsKey" => "",
            //"sqsSecret" => "",
            //"sqsQueue" => "438663597141/Studenthub",
            //"sqsEndpoint" => "http://ec2-18-130-75-235.eu-west-2.compute.amazonaws.com:3001"
        ],
        'xero' => [
            'class' => 'common\components\Xero',
            'clientId' => 'EAFC4996641A4A0CB86B501545518B15',
            "clientSecret" => "2vpFTWzxR8qXHIuJQsBof6eSDSw5kj_cpFdAaxjoY_Jwhwym",
            "xeroTenantId" => "c9895946-8dcc-4670-87be-ec1cca21c6d4"
        ],
        'mediaConvert' => [
            'class' => 'common\components\MediaConvert',
            'region' => 'eu-west-2', // based in London
            'endpoint' => 'https://ey3xqwxpb.mediaconvert.eu-west-2.amazonaws.com',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_KEY_AND_SECRET,
            'role' => 'arn:aws:iam::438663597141:role/MediaConvertPermissions',
            'jobQueue' =>  "arn:aws:mediaconvert:eu-west-2:438663597141:queues/Default",
            "key" => getenv('AWS_MEDIACONVERT_RAILWAY_ACCESS_KEY_ID') ?: null,
            "secret" => getenv('AWS_MEDIACONVERT_RAILWAY_SECRET_ACCESS_KEY') ?: null,
        ],
        'resourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_KEY_AND_SECRET,
            'region' => 'eu-west-2', // Bucket based in London
            'bucket' => 'studenthub-uploads',
            'key' => getenv('AWS_PERMANENT_S3_ACCESS_KEY_ID'),//railway-s3-access
            'secret' => getenv('AWS_PERMANENT_S3_SECRET_ACCESS_KEY'),
            /**
             * For Local Development, we access using key and secret
             * For Dev and Production servers, access is via server embedded IAM roles so no key/secret required
             *
             * You can access the bucket with:
             * https://studenthub-uploads.s3.amazonaws.com/
             * https://studenthub-uploads.s3.amazonaws.com/folderName/fileName.jpg
             */
        ],
        'yeaster' => [
            'class' => 'common\components\Yeaster',
            "apiEndpoint" => "http://ec2-18-130-75-235.eu-west-2.compute.amazonaws.com:3001"
        ],
        'urlManagerStaff' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => 'https://staff.api.studenthub.co/v1',
        ],
        'urlManagerCandidate' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => 'https://student.api.studenthub.co/v1',
        ],
        'urlManagerVerification' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => 'https://v.studenthub.co/'
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => $sentryDsn,
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
                        'environment' => $sentryEnvironment,
                        'traces_sample_rate' => $sentryTracesSampleRate,

                        // Disable notifications for malicious errors from 3rd party
                        // 'send_callback' => function($data) {
                        //     // Error Types to Ignore
                        //     $ignore_types = [
                        //         'yii\web\NotFoundHttpException',
                        //         'Page not found.'
                        //     ];
                        //
                        //     if (isset($data['exception']) &&
                        //         (in_array($data['exception']['values'][0]['type'], $ignore_types) ||
                        //         in_array($data['exception']['values'][0]['value'], $ignore_types))
                        //     ){
                        //         return false;
                        //     }
                        // },
                    ],
                    'context' => true // Write the context information. The default is true.
                ],
            ],
        ],
    ],
];
