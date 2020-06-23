<?php
return [
    'store_id' => $index + 1,
    'company_id' => $faker->numberBetween(4,10),
    'store_name' => $faker->company,
    'store_status' => 10,
    'store_created_at' => $faker->date('Y-m-d H:i:s'),
    'store_updated_at' => $faker->date('Y-m-d H:i:s'),
    'deleted' => '0'
];
