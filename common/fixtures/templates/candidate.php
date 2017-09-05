<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */
return [
        'candidate_id' => $index + 1,
        'candidate_uid' => $faker->uuid,
        'store_id' => $faker->realText($faker->numberBetween(1,3)),
        'bank_id' => $faker->realText($faker->numberBetween(1,3)),
        'university_id' => $faker->realText($faker->numberBetween(1,2)),
        'country_id' => $faker->realText($faker->numberBetween(1,5)),
        'bank_account_name' => $faker->firstName,
        'candidate_iban' => Yii::$app->getSecurity()->generateRandomString(10),
        'candidate_name' => $faker->firstName,
        'candidate_name_ar' => 'أكشاي باتيا',
        'candidate_personal_photo' => 'photos/'.$faker->image(),
        'candidate_email' => $faker->email,
        'candidate_phone' => $faker->phoneNumber,
        'candidate_address_line1' => $faker->address,
        'candidate_birth_date' => $faker->date('Y-m-d'),
        'candidate_civil_id' => $faker->unique(),
        'candidate_civil_expiry_date' => date('Y-m-d', strtotime('+1 month')),
        'candidate_civil_photo_front' => 'photos/'.$faker->image(),
        'candidate_civil_photo_back' => 'photos/'.$faker->image(),
        'candidate_hourly_rate' => $faker->randomFloat(2,1.0,1.9),
        'candidate_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'candidate_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('password_' . $index + 1),
        'candidate_password_reset_token' => Yii::$app->getSecurity()->generateRandomString(),
        'candidate_status' => 1,
        'approved' => 1,
        'candidate_created_at' => $faker->iso8601,
        'candidate_updated_at' => $faker->iso8601
];