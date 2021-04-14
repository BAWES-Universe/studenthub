<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'note_uuid' => $faker->uuid,
    'company_id' => $faker->numberBetween(1,10),
    'candidate_id' => $faker->numberBetween(1,10),
    'request_uuid' => $faker->uuid,
    'invitation_uuid' => $faker->uuid,
    'suggestion_uuid' => $faker->uuid,
    'contact_uuid' => $faker->uuid,
    'fulltimer_uuid' => $faker->uuid,
    'note_type' => $faker->randomElement([
        "Internal Note", "Phone Call", "Email", "Meeting", "Interview", "Task"
    ]),
    'note_text' => $faker->word,
    'note_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'note_updated_datetime' => $faker->date('Y-m-d H:i:s'),
    'created_by' => $faker->numberBetween(1,3),
    'updated_by' => $faker->numberBetween(1,3),
];
