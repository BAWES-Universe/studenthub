<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'phone_uuid' => $index + 1,
    'contact_uuid' => $index + 1,
    'phone_number' => $faker->email,
    'phone_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'phone_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
