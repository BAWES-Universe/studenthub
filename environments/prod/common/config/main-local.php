<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=studenthub-prod.cluster-c8mekjvvbygf.eu-west-2.rds.amazonaws.com;dbname=studenthub',
            'username' => 'bawes',
            'password' => 'bawes12student!hub',
            // Old config
            // 'dsn' => 'mysql:host=10.131.43.120;dbname=payroll',
            // 'username' => 'studenthubpayrollbawes',
            // 'password' => 'bawes12student!hub',
            'charset' => 'utf8',

            // common configuration for slaves
            'slaveConfig' => [
                'username' => 'bawes',
                'password' => 'bawes12student!hub',
                'attributes' => [
                    // use a smaller connection timeout
                    PDO::ATTR_TIMEOUT => 10,
                ],
            ],
            // list of slave configurations for Read-write splitting
            'slaves' => [
                ['dsn' => 'mysql:host=studenthub-prod.cluster-ro-c8mekjvvbygf.eu-west-2.rds.amazonaws.com;dbname=studenthub']
            ],

            // Enable Caching of Schema to Reduce SQL Queries
            'enableSchemaCache' => true,
            // Duration of schema cache.
            'schemaCacheDuration' => 3600, // 1 hr
            // Name of the cache component used to store schema information
            'schemaCache' => 'cache',
        ],
        'walletDb' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=wallet-prod.cluster-c8mekjvvbygf.eu-west-2.rds.amazonaws.com;dbname=bawes_wallet',
            'username' => 'wll3t1232',
            'password' => '24uJQLOx55q$',
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
            'apiKey' => 'imx4kpyVCXbi7sVy-zEvEITL63sQWisn',//QSw2ByGUITXFNjJVNNjyzxdbvYP9rXbG
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.elasticemail.com',
                'username' => 'contact@studenthub.co',
                'password' => 'B53B9967191B1466BA30B027F95A726ECE49',
                'port' => '2525',
                'encryption' => 'tls'

                /*'class' => 'Swift_SmtpTransport',
                'host' => 'email-smtp.eu-west-1.amazonaws.com',
                'username' => 'AKIAWMITDJRKTH5HBB2O',
                'password' => 'BKyPcINpZJsEVnUrMGymff27eaIztgNwSWN7xI2960eJ',
                'port' => '587  ',
                'encryption' => 'tls',,

                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.eu.mailgun.org',
                'username' => 'postmaster@studenthub.co',
                'password' => '345f8ffa2c7eb8af3c398e53976f67b0-18e06deb-bdad79c2',
                'port' => '587',
                'encryption' => 'tls'
                // 'plugins' => [
                //     [
                //         'class' => 'Openbuildings\Swiftmailer\CssInlinerPlugin',
                //     ],
                // ],*/
            ],
        ],
        /*
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
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
        ],
        'mediaConvert' => [
            'class' => 'common\components\MediaConvert',
            'region' => 'eu-west-2', // based in London
            'endpoint' => 'https://ey3xqwxpb.mediaconvert.eu-west-2.amazonaws.com',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'role' => 'arn:aws:iam::438663597141:role/MediaConvertPermissions',
            'jobQueue' =>  "arn:aws:mediaconvert:eu-west-2:438663597141:queues/Default"
        ],
        'resourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'region' => 'eu-west-2', // Bucket based in London
            'bucket' => 'studenthub-uploads',
            /**
             * For Local Development, we access using key and secret
             * For Dev and Production servers, access is via server embedded IAM roles so no key/secret required
             *
             * You can access the bucket with:
             * https://studenthub-uploads.s3.amazonaws.com/
             * https://studenthub-uploads.s3.amazonaws.com/folderName/fileName.jpg
             */
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
                        'environment' => 'production',

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
