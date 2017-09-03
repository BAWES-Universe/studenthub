<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'bank_name' => $faker->company . " Bank",
    'bank_swift_code' => $faker->numerify('####KWKW'),
    'bank_address' => $faker->address,
    'bank_transfer_type' => $faker->randomElement(['SWF', 'LCL', 'TRF'])
];
