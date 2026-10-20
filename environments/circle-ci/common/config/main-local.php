<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname=payroll_test',
            'username' => 'bawes',
            'password' => 'passw0rd',
            'charset' => 'utf8',
        ],
        'walletDb' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=127.0.0.1;dbname=wallet_test',//todo: replace with wallet db from sandbox
            'username' => 'bawes',
            'password' => 'passw0rd',
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
            'baseUrl' => 'http://localhost:8888/studenthub/verification/web/v1'
        ],
        'eventManager' => [
            'class' => 'common\components\EventManager',
            "sqsRagion" => "eu-west-2",
            "sqsKey" => "",
            "sqsSecret" => "",
            "sqsQueue" => "438663597141/StudenthubDev"
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
        //for testing purpose
        'resourceManager' => [
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
                        'environment' => 'circle-ci',
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
