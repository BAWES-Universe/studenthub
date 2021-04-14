<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'bank_name' => $faker->company . " Bank",
    'bank_iban_code' => 'KWKW',
    'bank_swift_code' => $faker->numerify('####KWKW'),
    'bank_address' => $faker->streetAddress,
    'bank_transfer_type' => $faker->randomElement(['SWF', 'LCL', 'TRF']),
    'deleted' => 0
];
