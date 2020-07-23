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
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.sendgrid.net',
                'username' => 'apikey',//sh-payroll
                'password' => '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',//WeLoveSHTrainingProg!121',
                'port' => '587',
                'encryption' => 'tls',
            ],
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
        'log' => [
            'targets' => [
                [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => 'https://6cbd2100e1ff41e7875352655ffbf50d:e18336b09d864b29aa12aca3fbc6706c@sentry.io/168200',
                    'levels' => ['error', 'warning'],

                    'clientOptions' => [
                        //which environment are we running this on?
                        'environment' => 'production',
                        'excluded_exceptions' => [
                            'yii\web\BadRequestHttpException',
                            'yii\web\UnauthorizedHttpException',
                            'yii\web\NotFoundHttpException',
                            'yii\web\HttpException:400',
                            'yii\web\HttpException:401',
                            'yii\web\HttpException:404',
                        ],
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
