<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
$index1 = $index % 1000;//faker->unique()->numberBetween(0, 1000);
$index2 = $index % 500;//faker->unique()->numberBetween(0, 1000);

$contact_uuid = Yii::$app->db->createCommand('SELECT contact_uuid from company_contact order by rand() limit 1')->queryScalar();
$staff_uuid = Yii::$app->db->createCommand('SELECT staff_uuid from staff order by rand() limit 1')->queryScalar();
$company_id = Yii::$app->db->createCommand('SELECT company_id from company order by rand() limit 1')->queryScalar();

return [
    'request_uuid' => 'request_'.$faker->uuid,
    'company_id' => $company_id,
    'contact_uuid' => $contact_uuid,
    'staff_uuid' => $staff_uuid,
    'request_created_by' => $faker->numberBetween(1,10),
    'request_updated_by' => $faker->numberBetween(1,10),
    'request_position_type' => rand(1, 2),
    'request_position_title' =>  $faker->words(10,1),
    'request_number_of_employees' => rand(10, 20),
    'request_additional_info' => $faker->sentence(1,10),
    'request_location' => $faker->sentence(1,10),
    'request_job_description' => $faker->sentence(1,10),
    'request_compensation' => $faker->sentence(1,10),
    'request_status' => $faker->randomElement([
        'pending',
        'started',
        'delivered',
        'cancelled'
    ]),
    'request_feedback' => $faker->sentence(1,10),
    'request_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'request_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
