<?php

$contact_uuid = Yii::$app->db->createCommand('SELECT contact_uuid from company_contact order by rand() limit 1')->queryScalar();

$brand_uuid = Yii::$app->db->createCommand('SELECT brand_uuid from brand order by rand() limit 1')->queryScalar();

$mall_uuid = Yii::$app->db->createCommand('SELECT mall_uuid from mall order by rand() limit 1')->queryScalar();

return [
    'store_id' => $index + 1,
    'company_id' => $faker->numberBetween(4,10),
    'store_manager_uuid' => $contact_uuid,
    'brand_uuid' => $brand_uuid, 
    'mall_uuid' => $mall_uuid,
    'store_name' => $faker->company,
    'store_location' => $faker->address,
    'store_total_candidates' => $faker->numberBetween(10, 100),
    'store_status' => 10,
    'store_created_at' => $faker->date('Y-m-d H:i:s'),
    'store_updated_at' => $faker->date('Y-m-d H:i:s'),
    'deleted' => '0'
];
