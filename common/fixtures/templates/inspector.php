<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'inspector_uuid' => $faker->uuid,
    'inspector_name' => $faker->firstName,
    'inspector_email' => $faker->email,
    'inspector_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
    'inspector_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
    'inspector_password_reset_token' => Yii::$app->getSecurity()->generateRandomString(),
    'inspector_status' => 10,
    'inspector_deleted' => 0,
    'inspector_created_at' => $faker->date('Y-m-d H:i:s'),
    'inspector_updated_at' => $faker->date('Y-m-d H:i:s'),
];
