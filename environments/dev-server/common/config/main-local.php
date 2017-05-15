<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=devpayroll',
            'username' => 'devPayrollUser',
            'password' => 'devpay',
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
        // 'log' => [
        //     'targets' => [
        //         [
        //             'class' => 'notamedia\sentry\SentryTarget',
        //             'dsn' => 'https://b4bef13c94834f7b9a422b4fefa6d73f:096f6ba42f1f4edfaa2c8d47c0ec9f80@sentry.io/168205',
        //             'levels' => ['error', 'warning'],
        //             'context' => true // Write the context information. The default is true.
        //         ],
        //     ],
        // ],
    ],
];
