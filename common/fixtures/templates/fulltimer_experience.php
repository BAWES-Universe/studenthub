<?php

$index1 = $index % 10;
$fulltimer_uuid = Yii::$app->db->createCommand('SELECT fulltimer_uuid from fulltimer limit ' . $index1 . ',1')->queryScalar();

/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'fulltimer_experience_id'=> $index + 1,
    'fulltimer_uuid'=> $fulltimer_uuid,
    'experience' => $faker->word,
    'fulltimer_experience_created_at' => $faker->date('Y-m-d H:i:s'), 
    'deleted' => 0
];