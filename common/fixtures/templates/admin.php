<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'admin_name' => $faker->firstName,
    'admin_email' => $faker->email,
    'admin_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
    'admin_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('password_' . $index),
    'admin_password_reset_token' => null,
    'admin_status' => 10,
    'admin_created_at' => $faker->iso8601,
    'admin_updated_at' => $faker->iso8601
];
