<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'suggestion_uuid' => $faker->uuid,
    'request_uuid' => $index + 1,
    'fulltimer_uuid' => $index + 1,
    'candidate_id' => $index + 1,
    'note_uuid' => $index + 1,
    'suggestion_status' => $faker->numberBetween(0,3),
    'suggestion_datetime' => $faker->date('Y-m-d H:i:s')
];
