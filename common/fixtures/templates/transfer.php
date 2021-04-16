<?php

return [
    'transfer_id' => $index + 1,
    'parent_transfer_id' => null,
    'company_id' => rand(10,100),
    'total' => rand(10,100),
    'company_total' => rand(10,100),
    'payment_received_on' => $faker->date('Y-m-d H:i:s'),
    'transfer_status' => $faker->numberBetween(1,10),
    'start_date' => $faker->date('Y-m-d H:i:s'),
    'end_date' => $faker->date('Y-m-d H:i:s'),
    'transfer_created_by' => 1,
    'transfer_updated_by' => 1,
    'transfer_created_at' => $faker->date('Y-m-d H:i:s'),
    'transfer_updated_at' => $faker->date('Y-m-d H:i:s'),
    'deleted' => 0,
];
