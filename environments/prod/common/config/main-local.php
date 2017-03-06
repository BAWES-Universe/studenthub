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
    ],
];
