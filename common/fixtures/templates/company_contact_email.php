<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'email_uuid' => $index + 1,
    'contact_uuid' => $index + 1,
    'email_address' => $faker->email,
    'email_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'email_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
