<?php

$index1 = $index % 1000;//faker->unique()->numberBetween(0, 1000);

$contact_uuid = Yii::$app->db->createCommand('SELECT contact_uuid from contact limit ' . $index1 . ',1')->queryScalar();

return [
	'token_id' => $index + 1, // this token belongs to this admin, needs to match # of admins/their PK
	'contact_uuid' => $contact_uuid,
	'token_value' => Yii::$app->getSecurity()->generateRandomString(),
	'token_device' => null,
	'token_device_id' => null,
	'token_status' => 1,
	'token_last_used_datetime' => null,
	'token_expiry_datetime' => null,
	'token_created_datetime' => $faker->iso8601
];
