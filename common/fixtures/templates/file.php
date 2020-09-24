<?php

return [
    'file_uuid' => $faker->uuid,
    'company_id' => $index + 1,
    'file_title' => $faker->name,
    'file_description' => $faker->sentence(7, true),
    'file_name' => $faker->name, 
    'file_type' => 'image/jpeg', 
    'file_size' => '1000',
    'file_s3_path' => $faker->name,
    'file_created_datetime' => $faker->date('Y-m-d H:i:s')
];
