<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'brand_uuid' => $faker->uuid,
    'company_id' => $faker->numberBetween(1,10),
    'brand_name_en' => $faker->company,
    'brand_name_ar' => $faker->company,
    'brand_logo' => 'photos/photo-1497874516406.png',
    'brand_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'brand_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
