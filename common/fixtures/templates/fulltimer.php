<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'fulltimer_uuid' => $faker->uuid,
    'nationality_id' => $faker->numberBetween(1,5),
    'country_id' => $faker->numberBetween(1,5),
    'university_id' => $faker->numberBetween(1,5),
    'fulltimer_employed' => 1,
    'fulltimer_gender' => 1,
    'fulltimer_birth_date' => $faker->date('Y-m-d'),
    'fulltimer_driving_license' => 1,
    'fulltimer_area_uuid' => $faker->numberBetween(1,5),
    'fulltimer_latitude' => 70,
    'fulltimer_longitude' => 70,
    'fulltimer_name' => $faker->firstName,
    'fulltimer_phone' => $faker->e164PhoneNumber,
    'fulltimer_email' => $faker->email,
    'fulltimer_current_salary' => $faker->randomNumber(5),
    'fulltimer_expected_salary' => $faker->randomNumber(5),
    'fulltimer_pdf_cv' =>  'test.pdf',
    'fulltimer_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'fulltimer_updated_datetime' => $faker->date('Y-m-d H:i:s'),
];
