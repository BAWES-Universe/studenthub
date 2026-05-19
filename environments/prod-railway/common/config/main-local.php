<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => getenv('DB_DSN') ?: 'mysql:host=mysql.railway.internal;dbname=railway',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
        ],
        'walletDb' => [
            'class' => 'yii\db\Connection',
            'dsn' => getenv('WALLET_DB_DSN') ?: 'mysql:host=mysql-5abl.railway.internal;dbname=railway',
            'username' => getenv('WALLET_DB_USERNAME') ?: 'root',
            'password' => getenv('WALLET_DB_PASSWORD'),
            'charset' => 'utf8',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
        ],
        'walletManager' => [
            'class' => 'common\components\WalletManager',
            'apiKey' => getenv('WALLET_API_KEY'),
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => getenv('REDIS_HOST') ?: 'redis.railway.internal',
            'username' => getenv('REDIS_USERNAME') ?: 'default',
            'password' => getenv('REDIS_PASSWORD'),
            'port' => (int)(getenv('REDIS_PORT') ?: 6379),
            'database' => (int)(getenv('REDIS_DB') ?: 0),
        ],
        'cache' => [
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
            ],
        ],
        'eventManager' => [
            'class' => 'common\components\EventManager',
        ],
        'xero' => [
            'class' => 'common\components\Xero',
            'clientId' => getenv('XERO_CLIENT_ID'),
            "clientSecret" => getenv('XERO_CLIENT_SECRET'),
            "xeroTenantId" => getenv('XERO_TENANT_ID')
        ],
        'mediaConvert' => [
            'class' => 'common\components\MediaConvert',
            'region' => getenv('AWS_REGION') ?: 'eu-west-2',
            'endpoint' => getenv('AWS_MEDIACONVERT_ENDPOINT'),
            'authMethod' => \common\components\S3ResourceManager::AUTH_VIA_KEY_AND_SECRET,
            'role' => getenv('AWS_MEDIACONVERT_ROLE_ARN'),
            'jobQueue' =>  getenv('AWS_MEDIACONVERT_QUEUE_ARN'),
            "key" => getenv('AWS_MEDIACONVERT_KEY'),
            "secret" => getenv('AWS_MEDIACONVERT_SECRET')
        ],
        'resourceManager' => [
            'class' => 'common\components\S3ResourceManager',
            'authMethod' => (int)(getenv('AWS_AUTH_METHOD') ?: \common\components\S3ResourceManager::AUTH_VIA_KEY_AND_SECRET),
            'region' => getenv('AWS_REGION') ?: 'eu-west-2',
            'bucket' => getenv('AWS_S3_BUCKET') ?: 'studenthub-uploads',
            'key' => getenv('AWS_PERMANENT_S3_ACCESS_KEY_ID'),
            'secret' => getenv('AWS_PERMANENT_S3_SECRET_ACCESS_KEY'),
        ],
        'yeaster' => [
            'class' => 'common\components\Yeaster',
            "apiEndpoint" => getenv('YEASTER_API_ENDPOINT') ?: "http://ec2-18-130-75-235.eu-west-2.compute.amazonaws.com:3001"
        ],
        'log' => [
            'targets' => [
                [
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
                    'context' => true
                ],
            ],
        ],
    ],
];
