<?php 

$request_uuid = Yii::$app->db->createCommand('SELECT request_uuid from request order by rand() limit 1')->queryScalar();

$staff_id = Yii::$app->db->createCommand('SELECT staff_id from staff order by rand() limit 1')->queryScalar();

return [
	'activity_uuid' => 'suggestion_'.$faker->uuid,
	'request_uuid' => $request_uuid, 
	'staff_id' => $staff_id, 
	'activity_detail' => $faker->words(5), 
	'activity_created_datetime' => $faker->date('Y-m-d H:i:s'),
	'activity_updated_datetime' => $faker->date('Y-m-d H:i:s'),
];