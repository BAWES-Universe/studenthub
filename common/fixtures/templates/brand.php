<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'brand_uuid' => $index + 1,
    'company_id' => $index + 1,
    'brand_name_en' => $faker->company,
    'brand_name_ar' => $faker->company,
    'brand_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'brand_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
