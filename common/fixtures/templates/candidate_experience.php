<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'candidate_experience_id' => $index + 1,
    'candidate_id' => $index + 1,
    'experience' => $faker->word,
    'deleted' => 0,
    'candidate_experience_created_at' => $faker->date('Y-m-d H:i:s')
];
