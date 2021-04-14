<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
$index1 = $index % 1000;//faker->unique()->numberBetween(0, 1000);
$index2 = $index % 500;//faker->unique()->numberBetween(0, 1000);
$request_uuid = Yii::$app->db->createCommand('SELECT request_uuid from request order by rand() limit 1')->queryScalar();

return [
    'activity_uuid' => 'act_req_'.$faker->uuid,
    'request_uuid' => $request_uuid,
    'staff_id' => $faker->numberBetween(1,2),
    'activity_detail' =>  $faker->words(10,1),
    'activity_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'activity_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
