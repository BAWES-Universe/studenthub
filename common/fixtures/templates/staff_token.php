<?php
$index1 = $index % 1000; //faker->unique()->numberBetween(0, 1000);
$staff_id = Yii::$app->db->createCommand('SELECT staff_id from staff limit '.$index1.',1')->queryScalar();

return [
	'token_id' => $index + 1, // this token belongs to this admin, needs to match # of admins/their PK
	'staff_id' => $staff_id,
	'token_value' => Yii::$app->getSecurity()->generateRandomString(),
	'token_device' => null,
	'token_device_id' => null,
	'token_status' => 1,
	'token_last_used_datetime' => null,
	'token_expiry_datetime' => null,
	'token_created_datetime' => $faker->date('Y-m-d H:i:s')
];
