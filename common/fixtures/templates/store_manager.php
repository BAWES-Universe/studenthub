<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'store_manager_uuid' => $faker->uuid,
    "company_id" => $index,
    "store_id" => $index,
    'name' => $faker->firstName,
    'email' => $faker->email,
    'new_email' => $faker->email,
    "phone_number" => $faker->phoneNumber,
    'email_verification' => 1,
    'limit_email' => $faker->date('Y-m-d H:i:s'),
    'password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
    'password_reset_token' => Yii::$app->getSecurity()->generateRandomString(),
    'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
    'created_at' => $faker->date('Y-m-d H:i:s'),
    'updated_at' => $faker->date('Y-m-d H:i:s')
];
