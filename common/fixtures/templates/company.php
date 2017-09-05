<?php

return [
    	'company_id' => $index + 1,
    	'parent_company_id' => NULL,
    	'company_name' => $faker->company,
    	'company_email' => $faker->companyEmail,
    	'company_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
    	'company_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('password_' . $index + 1),
    	'company_password_reset_token' => Yii::$app->getSecurity()->generateRandomString(),
    	'company_status' => 10,
    	'company_created_at' => $faker->iso8601,
    	'company_updated_at' => $faker->iso8601
];