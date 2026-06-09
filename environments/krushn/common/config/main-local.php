<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost:8889;dbname=studenthub',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
        ],
        'yeaster' => [
            'class' => 'common\components\Yeaster',
            'microserviceApiKey' => getenv('YEASTER_MICROSERVICE_API_KEY') ?: null,
            "apiEndpoint" => "http://localhost:3001"
        ],
        'walletDb' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost:8889;dbname=wallet',
            'username' => 'root',
            'password' => 'root',
            'charset' => 'utf8',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
            //'class' => 'yii\caching\FileCache',
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
        'walletManager' => [
            'class' => 'common\components\WalletManager',
            'apiKey' => getenv('WALLET_API_KEY') ?: null,
            'apiEndpoint' => 'http://localhost/wallet/webhook/web/v1',
            'companyWalletUserID' => 'user_fcac8a5f-52a2-11ed-a68e-d85ed3a264df'
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
            'baseUrl' => 'http://localhost:8888/bawes/studenthub/staff/web/v1'
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
            'baseUrl' => 'http://localhost:8888/studenthub/verification/web'
        ],
        'eventManager' => [
            'class' => 'common\components\EventManager',
            'sqsEndpointApiKey' => getenv('EVENT_MANAGER_ENDPOINT_API_KEY') ?: null,
            // "sqsRagion" => "eu-west-2",
            // "sqsKey" => "AKIAWMITDJRKXNWDOBNJ",
            //  "sqsSecret" => "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",
            //  "sqsQueue" => "438663597141/StudenthubDev",
            // "sqsEndpoint" => "http://localhost:3001"
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
            'key' => 'AKIAWMITDJRKVN5ODY2X',
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
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ]
    ],
];
