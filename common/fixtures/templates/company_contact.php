<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */

$index1 = $index % 1000;//faker->unique()->numberBetween(0, 1000);

$contact_uuid = Yii::$app->db->createCommand('SELECT contact_uuid from contact limit ' . $index1 . ',1')->queryScalar();

return [
    'company_contact_uuid' => $faker->uuid,
    'contact_uuid' => $contact_uuid,
    'company_id' => $faker->numberBetween(1,10),
    'contact_position' => $faker->jobTitle,
    'allow_access' => 1,
    'created_at' => $faker->date('Y-m-d H:i:s'),
    'updated_at' => $faker->date('Y-m-d H:i:s'),
    'created_by' => 1,
    'updated_by' => 1
];
