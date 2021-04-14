<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'candidate_note_uuid' => 'can_nte_'.$faker->uuid,
    'candidate_id' => $index + 1,
    'note_text' => $faker->word,
    'created_by' => 1,
    'updated_by' => 1,
    'note_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'note_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
