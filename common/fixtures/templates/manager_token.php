<?php

return [
    'token_uuid' => $index, // this token belongs to this admin, needs to match # of admins/their PK
    'store_manager_uuid' => $index,
    'token_value' => Yii::$app->getSecurity()->generateRandomString(),
    'token_device' => null,
    'token_device_id' => null,
    'token_status' => 1,
    'token_last_used_datetime' => null,
    'token_expiry_datetime' => null,
    'token_created_datetime' => $faker->date('Y-m-d H:i:s')
];
