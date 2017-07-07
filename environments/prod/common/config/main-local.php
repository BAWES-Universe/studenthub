<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=10.131.43.120;dbname=payroll',
            'username' => 'studenthubpayrollbawes',
            'password' => 'bawes12student!hub',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            'transport' => [
                'class' => 'Swift_SmtpTransport',
                'host' => 'smtp.sendgrid.net',
                'username' => 'sh-payroll',
                'password' => 'WeLoveSHTrainingProg!121',
                'port' => '587',
                'encryption' => 'tls',
            ],
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => 'https://6cbd2100e1ff41e7875352655ffbf50d:e18336b09d864b29aa12aca3fbc6706c@sentry.io/168200',
                    'levels' => ['error', 'warning'],
                    'context' => true // Write the context information. The default is true.
                ],
            ],
        ],
    ],
];
