<?php

return [
    'candidate_id' => 1,
    'store_id' => $faker->realText($faker->numberBetween(1,3)),
    'start_date' => $faker->date('Y-m-d'),
    'end_date' => null,
    'candidate_hourly_rate' => $faker->randomFloat(2,1.0,1.9),
];