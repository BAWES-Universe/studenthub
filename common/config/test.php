<?php
return [
    'id' => 'StudentHub Internship Program - Test',
    'timeZone' => 'Asia/Kuwait',
    'basePath' => dirname(__DIR__),
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
    	//to fix error for BlamableBehaviour in unit testing
        'user' => [
            'identityClass' => 'common\models\Staff',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null
        ],
    ]
];
