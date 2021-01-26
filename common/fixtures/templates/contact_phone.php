<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */

$index1 = $index % 1000;//faker->unique()->numberBetween(0, 1000);
$contact_uuid = Yii::$app->db->createCommand('SELECT contact_uuid from contact limit ' . $index1 . ',1')->queryScalar();

return [
    'phone_uuid' => $faker->uuid,
    'contact_uuid' => $contact_uuid,
    'phone_number' => $faker->email,
    'phone_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'phone_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
