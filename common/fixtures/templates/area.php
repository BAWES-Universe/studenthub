<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'area_uuid' => $faker->uuid,
    'area_name_en' => $faker->citySuffix,
    'area_name_ar' => $faker->citySuffix,
    'area_created_at' => $faker->date('Y-m-d H:i:s'),
    'area_updated_at' => $faker->date('Y-m-d H:i:s'),
    'area_created_by' => 1,
    'area_updated_by' => 1
];
