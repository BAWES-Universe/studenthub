<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'candidate_skill_id' => $index + 1,
    'candidate_id' => $index + 1,
    'skill' => $faker->word,
    'candidate_skill_created_at' => $faker->date('Y-m-d H:i:s')
];
