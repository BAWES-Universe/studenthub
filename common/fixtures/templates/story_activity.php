<?php 

$story_uuid = Yii::$app->db->createCommand('SELECT story_uuid from story order by rand() limit 1')->queryScalar();

return [
	'story_activity_uuid' => 'story_activity_' . $faker->uuid, 
	'story_uuid' => $story_uuid, 
	'staff_id' => $staff_id, 
	'activity_time_spent' => 24*60*60,
	'activity_status' => $faker->numberBetween(0, 5),
	'activity_created_at' => $faker->date('Y-m-d H:i:s'),
	'activity_last_updated_at' => $faker->date('Y-m-d H:i:s'),
]