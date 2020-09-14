<?php

return [
    'transfer_id' =>  $faker->numberBetween(1,3),
    'invoice_date' => $faker->date('Y-m-d'),
    'invoice_status' => 'Paid',
    'deleted' => '0',
];
