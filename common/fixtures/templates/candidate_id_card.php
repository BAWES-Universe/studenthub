<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'candidate_id' => $index + 1,
    'expiry_date' => $index % 2? date('Y-m-d', strtotime('+1 month')) : date('Y-m-d', strtotime('-1 month')),
    'created_at' => $faker->iso8601,
    'updated_at' => $faker->iso8601
];
