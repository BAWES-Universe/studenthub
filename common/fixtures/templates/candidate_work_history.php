<?php
$index1 = $index % 1000; //faker->unique()->numberBetween(0, 1000);

$candidate = Yii::$app->db->createCommand('SELECT * from candidate limit '.$index1.',1')->queryOne();

return [
    'candidate_id' => $candidate['candidate_id'],
    'store_id' => $candidate['store_id'],
    'start_date' => $faker->date('Y-m-d'),
    'end_date' => null,
    'candidate_hourly_rate' => $faker->randomFloat(2,1.0,1.9),
];
