<?php

return [
    	'company_id' => $index + 1,
    	'parent_company_id' => NULL,
    	'company_name' => $faker->company,
    	'company_email' => $faker->companyEmail,
    	'company_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
    	'company_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
    	'company_password_reset_token' => Yii::$app->getSecurity()->generateRandomString(),
        'company_bonus_commission' => $faker->numberBetween(10,20),
        'company_hourly_rate' => $faker->numberBetween(1,10),
    	'company_status' => 10,
    	'company_created_at' => $faker->date('Y-m-d H:i:s'),
    	'company_updated_at' =>$faker->date('Y-m-d H:i:s'),
];
