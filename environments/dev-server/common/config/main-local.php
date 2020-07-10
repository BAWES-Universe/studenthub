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
                'username' => 'apikey',//sh-payroll
                'password' => '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',//WeLoveSHTrainingProg!121',
                'port' => '587',
                'encryption' => 'tls',
            ],
        ],
        'urlManagerStaff' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => 'http://payroll-staff.dev.studenthub.co/v1',
        ],
        /*'log' => [
            'targets' => [
                [
                    'class' => 'notamedia\sentry\SentryTarget',
                    'dsn' => 'https://6cbd2100e1ff41e7875352655ffbf50d:e18336b09d864b29aa12aca3fbc6706c@sentry.io/168200',
                    'levels' => ['error', 'warning'],
                    'clientOptions' => [
                        //which environment are we running this on?
                        'environment' => 'dev-server',
                        // Disable notifications for malicious errors from 3rd party
                        'send_callback' => function($data) {
                            // Error Types to Ignore
                            $ignore_types = [
                                'yii\web\NotFoundHttpException',
                                'Page not found.'
                            ];

                            if (isset($data['exception']) &&
                                (in_array($data['exception']['values'][0]['type'], $ignore_types) ||
                                in_array($data['exception']['values'][0]['value'], $ignore_types))
                            ){
                                return false;
                            }
                        },
                    ],
                    'context' => true // Write the context information. The default is true.
                ],
            ],
        ],*/
    ],
];
