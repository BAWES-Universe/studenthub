<?php 


$staff_id = Yii::$app->db->createCommand('SELECT staff_id from staff order by rand() limit 1')->queryScalar();

$suggestion_uuid = Yii::$app->db->createCommand('SELECT suggestion_uuid from suggestion order by rand() limit 1')->queryScalar();

$request_uuid = Yii::$app->db->createCommand('SELECT request_uuid from request order by rand() limit 1')->queryScalar();

return [
	'story_uuid' => 'story_' . $faker->uuid,
	'request_uuid' => $request_uuid,
	'suggestion_uuid' => $suggestion_uuid,
	'staff_id' => $staff_id,
	'story_status' => $faker->randomElement([
        'pending',
        'started',
        'delivered',
        'cancelled'
    ]),
	'is_old' => 0, 
	'story_time_spent' => 24*60*60,
	'story_created_at' => $faker->date('Y-m-d H:i:s'), 
	'story_last_updated_at' => $faker->date('Y-m-d H:i:s'),
];
