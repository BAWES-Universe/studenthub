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
            'dsn' => 'mysql:host=studenthub-prod.cluster-c8mekjvvbygf.eu-west-2.rds.amazonaws.com;dbname=wallet',
            'username' => 'bawes',
            'password' => 'bawes12student!hub',
            
            //'dsn' => 'mysql:host=wallet-prod.cluster-c8mekjvvbygf.eu-west-2.rds.amazonaws.com;dbname=bawes_wallet',
            //'username' => 'wll3t1232',
            //'password' => '24uJQLOx55q$',
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
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
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
            'class' => 'yii\redis\Cache',
            //'class' => 'yii\caching\FileCache',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'scheme' => getenv('MAIL_SCHEME') ?: 'smtp',
                'host' => getenv('MAIL_HOST') ?: 'smtp.resend.com',
                'username' => getenv('MAIL_USERNAME') ?: 'resend',
                'password' => getenv('MAIL_PASSWORD') ?: '',
                'port' => (int)(getenv('MAIL_PORT') ?: 587),
                'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
            ],
        ],
        'eventManager' => [
            'class' => 'common\components\EventManager',
            //todo: commenting down as it's slowing down all apis
            //"sqsRagion" => "eu-west-2",
            //"sqsKey" => "AKIAWMITDJRKXNWDOBNJ",
            //"sqsSecret" => "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",
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
