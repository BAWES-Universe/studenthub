<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'admin_id' => $index + 1,
    'admin_name' => $faker->firstName,
    'admin_email' => $faker->email,
    'admin_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
    'admin_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
    'admin_password_reset_token' => null,
    'admin_status' => 10,
    'admin_created_at' => $faker->date('Y-m-d H:i:s'),
    'admin_updated_at' => $faker->date('Y-m-d H:i:s'),
];
