<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'candidate_id' => $index + 1,
    'token_value' => Yii::$app->getSecurity()->generateRandomString(),
    'token_device' => null,
    'token_device_id' => null,
    'token_status' => 1,
    'token_last_used_datetime' => $faker->iso8601,
    'token_expiry_datetime' =>  $faker->iso8601,
    'token_created_datetime' => $faker->iso8601
];
