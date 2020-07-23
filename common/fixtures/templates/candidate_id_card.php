<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'candidate_id' => $index + 1,
    'expiry_date' => $index % 2? date('Y-m-d', strtotime('+1 month')) : date('Y-m-d', strtotime('-1 month')),
    'created_at' => $faker->date('Y-m-d H:i:s'),
    'updated_at' => $faker->date('Y-m-d H:i:s'),
];
