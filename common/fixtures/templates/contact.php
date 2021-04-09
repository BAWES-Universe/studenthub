<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'contact_uuid' => $faker->uuid,
    'contact_name' => $faker->firstName,
    'contact_email' => $faker->email,
    'contact_new_email' => $faker->email,
    'contact_email_verification' => 1,
    'contact_limit_email' => $faker->date('Y-m-d H:i:s'),
    'contact_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
    'contact_receive_email' => 1,
    'contact_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
	'contact_password_reset_token' => Yii::$app->getSecurity()->generateRandomString(),
    'contact_receive_notification' => 1,
    'contact_created_at' => $faker->date('Y-m-d H:i:s'),
    'contact_updated_at' => $faker->date('Y-m-d H:i:s')
];
