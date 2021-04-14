<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'area_uuid' => $faker->uuid,
    'country_id' => $index + 1,
    'area_name_en' => $faker->citySuffix,
    'area_name_ar' => $faker->citySuffix,
    'area_latitude' => $faker->latitude,
    'area_longitude' => $faker->longitude,
    'area_created_at' => $faker->date('Y-m-d H:i:s'),
    'area_updated_at' => $faker->date('Y-m-d H:i:s'),
    'area_created_by' => 1,
    'area_updated_by' => 1
];
