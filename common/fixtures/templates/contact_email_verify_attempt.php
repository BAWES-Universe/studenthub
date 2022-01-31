<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'ceva_uuid' => $index + 1,
    'email' => $faker->email,
    'code' => $faker->password,
    'ip_address' => $faker->localIpv4,
    'created_at' => $faker->date('Y-m-d H:i:s')
];
