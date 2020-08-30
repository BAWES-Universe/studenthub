<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'request_uuid' => $index + 1,
    'company_id' => $index + 1,
    'contact_uuid' => $index + 1,
    'request_created_by' => $index + 1,
    'request_updated_by' => $index + 1,
    'request_position_type' => rand(1, 2),
    'request_position_title' => $index + 1,
    'request_number_of_employees' => rand(10, 20),
    'request_additional_info' => $faker->sentence(1,10),
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
