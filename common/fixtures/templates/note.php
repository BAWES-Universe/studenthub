<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'note_uuid' => $index + 1,
    'company_id' => $index + 1,
    'staff_id' => $index + 1,
    'note_text' => $faker->word,
    'note_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'note_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
