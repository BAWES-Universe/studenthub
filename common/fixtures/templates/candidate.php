<?php

$index1 = $index % 1000;//faker->unique()->numberBetween(0, 1000);
$area_uuid = Yii::$app->db->createCommand('SELECT area_uuid from area limit ' . $index1 . ',1')->queryScalar();

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
        'candidate_gender' => $faker->numberBetween(1,3),
        'candidate_objective' => Yii::$app->getSecurity()->generateRandomString(10),
        'candidate_personal_photo' => 'photos/photo-1497874516406.png',
        'candidate_video' => null,
        'candidate_video_job_id' => null,
        'candidate_video_processed' => null,
        'candidate_email' => $faker->email,
        'candidate_new_email' => null,
        'candidate_email_verification' => $faker->numberBetween(0,1),
        'candidate_limit_email' => $faker->date('Y-m-d H:i:s'),
        'candidate_phone' => $faker->numberBetween($min = 1111111111, $max = 9999999999),
        'candidate_address_line1' => $faker->address,
        'candidate_area_uuid' => $area_uuid,
        'candidate_latitude' => $faker->latitude,
        'candidate_longitude' => $faker->longitude,
        'candidate_birth_date' => $faker->date('Y-m-d'),
        'candidate_civil_id' => $faker->numberBetween(1000000000,99999999999),
        'candidate_civil_expiry_date' => date('Y-m-d', strtotime('+1 month')),
        'candidate_civil_photo_front' => 'photos/photo-1497874516406.png',
        'candidate_civil_photo_back' => 'photos/photo-1497874516406.png',
        'candidate_driving_license' => $faker->numberBetween(1,2),
        'candidate_resume' => null,
        'candidate_hourly_rate' => $faker->randomFloat(2,1.0,1.9),
        'candidate_auth_key' => Yii::$app->getSecurity()->generateRandomString(),
        'candidate_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
        'candidate_password_reset_token' => Yii::$app->security->generateRandomString() . '_' . time(),
        'candidate_language_pref' => 'en',
        'candidate_job_search_status' => $faker->numberBetween(0,1),
        'candidate_committed' => 1,
        'candidate_preferred_time' => '11:00am to 6pm',
        'candidate_status' => 1,
        'approved' => 1,
        'candidate_mom_kuwaiti' => 1,
        'candidate_pending_profile' => '',
        'deleted' =>  $faker->numberBetween(0,1),
        'candidate_created_at' =>  $faker->date('Y-m-d H:i:s'),
        'candidate_updated_at' =>  $faker->date('Y-m-d H:i:s')
];
