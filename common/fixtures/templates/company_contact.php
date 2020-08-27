<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'contact_uuid' => $index + 1,
    'company_id' => $index + 1,
    'contact_name' => $faker->firstName,
    'contact_position' => $faker->jobTitle,
    'contact_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'contact_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
