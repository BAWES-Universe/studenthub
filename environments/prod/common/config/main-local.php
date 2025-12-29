<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'),
            'username' => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'charset' => getenv('DB_CHARSET') ?: 'utf8',
            'slaveConfig' => [
                'username' => getenv('DB_USER'),
                'password' => getenv('DB_PASSWORD'),
                'attributes' => [
                    PDO::ATTR_TIMEOUT => 10,
                ],
            ],
            'slaves' => [
                ['dsn' => 'mysql:host=' . getenv('DB_SLAVE_HOST') . ';dbname=' . getenv('DB_NAME')],
            ],
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => getenv('REDIS_HOST') ?: 'localhost',
            'port' => getenv('REDIS_PORT') ?: 6379,
            'database' => getenv('REDIS_DATABASE') ?: 0,
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'transport' => [
                'scheme' => getenv('SMTP_SCHEME') ?: 'smtp',
                'host' => getenv('SMTP_HOST') ?: 'email-smtp.eu-west-1.amazonaws.com',
                'username' => getenv('SMTP_USERNAME') ?: '',
                'password' => getenv('SMTP_PASSWORD') ?: '',
                'port' => getenv('SMTP_PORT') ?: 587,
            ],
        ],
        'eventManager' => [
            'class' => 'common\components\EventManager',
        ],
        'xero' => [
            'class' => 'common\components\Xero',
            'clientId' => getenv('XERO_CLIENT_ID') ?: '',
            'clientSecret' => getenv('XERO_CLIENT_SECRET') ?: '',
            'xeroTenantId' => getenv('XERO_TENANT_ID') ?: '',
        ],
        'mediaConvert' => [
            'class' => 'common\components\MediaConvert',
            'region' => getenv('AWS_MEDIACONVERT_REGION') ?: 'eu-west-2',
            'endpoint' => getenv('AWS_MEDIACONVERT_ENDPOINT') ?: 'https://ey3xqwxpb.mediaconvert.eu-west-2.amazonaws.com',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'role' => getenv('AWS_MEDIACONVERT_ROLE_ARN') ?: 'arn:aws:iam::438663597141:role/MediaConvertPermissions',
            'jobQueue' => getenv('AWS_MEDIACONVERT_QUEUE_ARN') ?: 'arn:aws:mediaconvert:eu-west-2:438663597141:queues/Default',
        ],
        'resourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_IAM_ROLE,
            'region' => getenv('AWS_MAIN_BUCKET_REGION') ?: 'eu-west-2',
            'bucket' => getenv('AWS_MAIN_BUCKET_NAME') ?: 'studenthub-uploads',
        ],
        'yeaster' => [
            'class' => 'common\components\Yeaster',
            'apiEndpoint' => getenv('YEASTER_API_ENDPOINT') ?: '',
        ],
        'urlManagerStaff' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => getenv('STAFF_API_BASE_URL') ?: 'https://staff.api.studenthub.co/v1',
        ],
        'urlManagerCandidate' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => getenv('CANDIDATE_API_BASE_URL') ?: 'https://student.api.studenthub.co/v1',
        ],
        'urlManagerVerification' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => getenv('VERIFICATION_BASE_URL') ?: 'https://v.studenthub.co/',
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
                        'environment' => getenv('SENTRY_ENVIRONMENT') ?: 'production',
                    ],
                    'context' => true,
                ] : null,
            ]),
        ],
    ],
];
