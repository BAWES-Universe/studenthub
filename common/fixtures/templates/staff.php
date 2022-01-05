<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'staff_id' => $index + 1,
    'staff_name' => $faker->firstName,
    'staff_email' => $faker->email,
    'staff_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
    'staff_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
    'staff_password_reset_token' => Yii::$app->getSecurity()->generateRandomString(),
    'staff_gmail_username' => null,
    'staff_gmail_password' => null,
    'staff_role' => 1,
    'staff_hourly_rate' => 1.6,
    'staff_status' => 10,
    'staff_created_at' => $faker->date('Y-m-d H:i:s'),
    'staff_updated_at' => $faker->date('Y-m-d H:i:s'),
    'deleted' => 0
];
