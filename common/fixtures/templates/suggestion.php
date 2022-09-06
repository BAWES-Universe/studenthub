<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */

$contact_uuid = Yii::$app->db->createCommand('SELECT contact_uuid from company_contact order by rand() limit 1 ')->queryScalar();
$request_uuid = Yii::$app->db->createCommand('SELECT request_uuid from request order by rand() limit 1')->queryScalar();
$fulltimer_uuid = Yii::$app->db->createCommand('SELECT fulltimer_uuid from fulltimer order by rand() limit 1')->queryScalar();
$candidate_id = Yii::$app->db->createCommand('SELECT candidate_id from candidate order by rand() limit 1')->queryScalar();
$note_uuid = Yii::$app->db->createCommand('SELECT note_uuid from note order by rand() limit 1')->queryScalar();
$story_uuid = Yii::$app->db->createCommand('SELECT story_uuid from note story by rand() limit 1')->queryScalar();

return [
    'suggestion_uuid' => 'suggestion_'.$faker->uuid,
    'request_uuid' => $request_uuid,
    'fulltimer_uuid' => $fulltimer_uuid,
    'candidate_id' => $candidate_id,
    'note_uuid' => $note_uuid,
    'story_uuid' => $story_uuid,
    'suggestion_status' => $faker->numberBetween(0,3),
    'mail_to_company' => 1,
    'suggestion_datetime' => $faker->date('Y-m-d H:i:s')
];
