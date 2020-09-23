<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'note_uuid' => $faker->uuid,
    'company_id' => $faker->numberBetween(1,10),
    'staff_id' => $faker->numberBetween(1,10),
    'note_text' => $faker->word,
    'note_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'note_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
