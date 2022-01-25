<?php

/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'video_log_uuid' => 'video_log_' . $faker->uuid,
    'candidate_id' => $index + 1,
    'ip_address' => $faker->ipv4(),
    'created_at' => $faker->date('Y-m-d H:i:s'),
];