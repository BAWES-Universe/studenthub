<?php

return [
    [
    	'company_id' => 1,
    	'parent_company_id' => NULL,
    	'company_name' => 'First Company - Parent',
    	'company_email' => 'company@company.com',
    	'company_auth_key' => 'f71hCXxe42UhlWuN_dbpCE5TcX7qN_vL',
    	'company_password_hash' => \Yii::$app->getSecurity()->generatePasswordHash('123456'),
    	'company_password_reset_token' => 'TnO9eI-XGIxeJGH7n57xSMyJfZ-5NKo6',
        'company_hourly_rate' => 2,
        'company_bonus_commission' => 10,
    	'company_status' => 10,
    	'company_created_at' => '2017-02-23 18:04:42',
    	'company_updated_at' => '2017-02-23 18:04:42'
    ],
    [
    	'company_id' => 2,
    	'parent_company_id' => 1,
    	'company_name' => 'Second Company - Child',
    	'company_email' => '',
    	'company_auth_key' => '',
    	'company_password_hash' => \Yii::$app->getSecurity()->generatePasswordHash('123456'),
    	'company_password_reset_token' => 'Tn19eI-XGIxeJGH7n57xSMyJfZ-5NKo6',
        'company_hourly_rate' => 2,
        'company_bonus_commission' => 10,
    	'company_status' => 10,
    	'company_created_at' => '2017-02-23 18:04:42',
    	'company_updated_at' => '2017-02-23 18:04:42'
    ],
    [
    	'company_id' => 3,
    	'parent_company_id' => null,
    	'company_name' => 'Without Child',
    	'company_email' => 'company3@bawes.net',
    	'company_auth_key' => '',
    	'company_password_hash' => \Yii::$app->getSecurity()->generatePasswordHash('123456'),
    	'company_password_reset_token' => 'Tn29eI-XGIxeJGH7n57xSMyJfZ-5NKo6',
        'company_hourly_rate' => 2,
        'company_bonus_commission' => 10,
    	'company_status' => 10,
    	'company_created_at' => '2017-02-23 18:04:42',
    	'company_updated_at' => '2017-02-23 18:04:42'
    ]
];