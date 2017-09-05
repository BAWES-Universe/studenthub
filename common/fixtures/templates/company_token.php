<?php

return [
	'token_id' => $index + 1, // this token belongs to this admin, needs to match # of admins/their PK
	'company_id' => $faker->realText($faker->numberBetween(1,3)),
	'token_value' => Yii::$app->getSecurity()->generateRandomString(),
	'token_device' => null,
	'token_device_id' => null,
	'token_status' => 1,
	'token_last_used_datetime' => null,
	'token_expiry_datetime' => null,
	'token_created_datetime' => $faker->iso8601
];
