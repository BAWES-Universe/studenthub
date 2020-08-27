<?php

return [
    'transfer_file_id' => $index + 1,
    'transfer_file_s3_path' => $faker->name,
    'transfer_amount' => rand(10,100),
    'transfer_file_created_at' => $faker->date('Y-m-d H:i:s'),
    'transfer_file_updated_at' => $faker->date('Y-m-d H:i:s'),
];
