<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
        'candidate_id' => $index + 1,
        'candidate_uid' => $faker->numberBetween(1000000000,99999999999),
        'store_id' => $faker->numberBetween(1,50),
        'bank_id' => $faker->numberBetween(1,3),
        'university_id' => $faker->numberBetween(1,2),
        'country_id' => $faker->numberBetween(1,5),
        'bank_account_name' => $faker->firstName,
        'candidate_iban' => Yii::$app->getSecurity()->generateRandomString(10),
        'candidate_name' => $faker->firstName,
        'candidate_name_ar' => 'أكشاي باتيا',
        'candidate_personal_photo' => 'photos/photo-1497874516406.png',
        'candidate_email' => $faker->email,
        'candidate_phone' => $faker->e164PhoneNumber,
        'candidate_address_line1' => $faker->address,
        'candidate_birth_date' => $faker->date('Y-m-d'),
        'candidate_civil_id' => $faker->numberBetween(1000000000,99999999999),
        'candidate_civil_expiry_date' => date('Y-m-d', strtotime('+1 month')),
        'candidate_civil_photo_front' => 'photos/photo-1497874516406.png',
        'candidate_civil_photo_back' => 'photos/photo-1497874516406.png',
        'candidate_hourly_rate' => $faker->randomFloat(2,1.0,1.9),
        'candidate_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'candidate_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
        'candidate_password_reset_token' => null,
        'candidate_status' => 1,
        'approved' => 1,
        'candidate_mom_kuwaiti' => 1,
        'candidate_created_at' =>  $faker->date('Y-m-d H:i:s'),
        'candidate_updated_at' =>  $faker->date('Y-m-d H:i:s'),
];
