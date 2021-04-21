<?php
$index1 = $index % 10;//faker->unique()->numberBetween(0, 1000);
$request_uuid = Yii::$app->db->createCommand('SELECT request_uuid from request limit ' . $index1 . ',1')->queryScalar();
$invitation_uuid = Yii::$app->db->createCommand('SELECT invitation_uuid from invitation limit ' . $index1 . ',1')->queryScalar();
$suggestion_uuid = Yii::$app->db->createCommand('SELECT suggestion_uuid from suggestion limit ' . $index1 . ',1')->queryScalar();
$contact_uuid = Yii::$app->db->createCommand('SELECT contact_uuid from contact limit ' . $index1 . ',1')->queryScalar();
$fulltimer_uuid = Yii::$app->db->createCommand('SELECT fulltimer_uuid from fulltimer limit ' . $index1 . ',1')->queryScalar();

/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'note_uuid' => 'note_' .$faker->uuid,
    'company_id' => $faker->numberBetween(1,10),
    'candidate_id' => $faker->numberBetween(1,10),
    'request_uuid' => $request_uuid,
    'invitation_uuid' => $invitation_uuid,
    'suggestion_uuid' => $suggestion_uuid,
    'contact_uuid' => $contact_uuid,
    'fulltimer_uuid' => $fulltimer_uuid,
    'note_type' => $faker->randomElement([
        "Internal Note", "Phone Call", "Email", "Meeting", "Interview", "Task"
    ]),
    'note_text' => $faker->word,
    'note_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'note_updated_datetime' => $faker->date('Y-m-d H:i:s'),
    'created_by' => $faker->numberBetween(1,3),
    'updated_by' => $faker->numberBetween(1,3),
];
