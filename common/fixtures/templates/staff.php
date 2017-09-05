<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
    'staff_name' => $faker->firstName,
    'staff_email' => $faker->email,
    'staff_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('password_' . $index+1)
];
