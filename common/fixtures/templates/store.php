<?php
return [
    'store_id' => $index + 1,
    'company_id' => $faker->realText($faker->numberBetween(1,3)),
    'store_name' => $faker->company,
    'store_status' => 10,
    'store_created_at' => $faker->iso8601,
    'store_updated_at' => $faker->iso8601,
    'deleted' => '0'
];