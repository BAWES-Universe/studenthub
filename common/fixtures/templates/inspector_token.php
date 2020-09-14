<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'token_uuid' => $faker->uuid, // this token belongs to this admin, needs to match # of admins/their PK
    'inspector_uuid' => $faker->uuid, // this token belongs to this admin, needs to match # of admins/their PK
    'token_value' => Yii::$app->getSecurity()->generateRandomString(),
    'token_device' => null,
    'token_device_id' => null,
    'token_status' => 1,
    'token_last_used_datetime' => null,
    'token_expiry_datetime' => null,
    'token_created_datetime' => $faker->iso8601
];
