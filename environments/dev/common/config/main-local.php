<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        ],
        'yeaster' => [
            'class' => 'common\components\Yeaster',
            'apiEndpoint' => getenv('YEASTER_API_ENDPOINT') ?: 'http://localhost:3001',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'xero' => [
            'class' => 'common\components\Xero',
            'clientId' => getenv('XERO_CLIENT_ID') ?: '',
            'clientSecret' => getenv('XERO_CLIENT_SECRET') ?: '',
            'xeroTenantId' => getenv('XERO_TENANT_ID') ?: '',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'useFileTransport' => filter_var(getenv('MAILER_USE_FILE_TRANSPORT') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        ],
        'urlManagerStaff' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => getenv('STAFF_API_BASE_URL') ?: 'https://staff.api.dev.studenthub.co/v1',
        ],
        'urlManagerCandidate' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => getenv('CANDIDATE_API_BASE_URL') ?: 'https://student.api.dev.studenthub.co/v1',
        ],
        'urlManagerVerification' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => getenv('VERIFICATION_BASE_URL') ?: 'https://v.dev.studenthub.co/',
        ],
        'eventManager' => [
            'class' => 'common\components\EventManager',
        ],
        'mediaConvert' => [
            'class' => 'common\components\MediaConvert',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'region' => getenv('AWS_MEDIACONVERT_REGION') ?: 'eu-west-2',
            'endpoint' => getenv('AWS_MEDIACONVERT_ENDPOINT') ?: 'https://ey3xqwxpb.mediaconvert.eu-west-2.amazonaws.com',
            'role' => getenv('AWS_MEDIACONVERT_ROLE_ARN') ?: 'arn:aws:iam::438663597141:role/MediaConvertPermissions',
            'jobQueue' => getenv('AWS_MEDIACONVERT_QUEUE_ARN') ?: 'arn:aws:mediaconvert:eu-west-2:438663597141:queues/Default',
        ],
        'resourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'region' => getenv('AWS_MAIN_BUCKET_REGION') ?: 'eu-west-2',
            'bucket' => getenv('AWS_MAIN_BUCKET_NAME') ?: 'studenthub-uploads-dev-server',
        ],
        'log' => [
            'targets' => array_filter([
                getenv('SENTRY_DSN') ? [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => getenv('SENTRY_DSN'),
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
                        'environment' => getenv('SENTRY_ENVIRONMENT') ?: 'dev',
                    ],
                    'context' => true,
                ] : null,
                [
                    'class' => 'common\components\SlackLogger',
                    'logVars' => [],
                    'levels' => ['info', 'warning'],
                    'categories' => ['admin\*', 'candidate\*', 'company\*', 'staff\*', 'remail\*', 'common\*', 'console\*'],
                ],
            ]),
        ],
    ],
];
