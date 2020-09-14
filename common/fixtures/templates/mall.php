<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'mall_uuid' => $faker->uuid,
    'mall_name_en' => $faker->name,
    'mall_name_ar' => $faker->name,
    'mall_created_datetime' => $faker->date('Y-m-d H:i:s'),
    'mall_updated_datetime' => $faker->date('Y-m-d H:i:s')
];
