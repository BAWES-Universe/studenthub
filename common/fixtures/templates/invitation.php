<?php

$index1 = $index % 50;
$story_uuid = Yii::$app->db->createCommand('SELECT story_uuid from story limit ' . $index1 . ',1')->queryScalar();

return [
    'invitation_uuid' => 'invitation_' .$faker->uuid,
    'candidate_id' => $index + 1,
    'request_uuid' => $index + 1,
    'story_uuid' => $story_uuid,
    'invitation_status' => $faker->numberBetween(0,3),
    'invitation_app_seen_at' =>  $faker->date('Y-m-d H:i:s'),
    'invitation_email_seen_at' =>  $faker->date('Y-m-d H:i:s'),
    'invitation_created_by_staff' => $index + 1,
    'invitation_updated_by_staff' => $index + 1,
    'invitation_created_by_company' => $index + 1,
    'invitation_updated_by_company' => $index + 1,
    'invitation_created_at' =>  $faker->date('Y-m-d H:i:s'),
    'invitation_updated_at' => $faker->date('Y-m-d H:i:s')
];
