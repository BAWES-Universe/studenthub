<?php 

$candidate_id = Yii::$app->db->createCommand('SELECT candidate_id from candidate order by rand() limit 1')->queryScalar();

$transfer_id = Yii::$app->db->createCommand('SELECT transfer_id from transfer order by rand() limit 1')->queryScalar();

$story_id = Yii::$app->db->createCommand('SELECT story_id from story order by rand() limit 1')->queryScalar();

$company_id = Yii::$app->db->createCommand('SELECT company_id from company order by rand() limit 1')->queryScalar(); 

$bank_id = Yii::$app->db->createCommand('SELECT bank_id from bank order by rand() limit 1')->queryScalar();

//$transfer_confirmation_id = Yii::$app->db->createCommand('SELECT transfer_confirmation_id from transfer_confirmation_ by rand() limit 1')->queryScalar();

$transfer_file_id = Yii::$app->db->createCommand('SELECT transfer_file_id from transfer_file order by rand() limit 1')->queryScalar();

$hours = $faker->numberBetween(1,10);

return [
	'tc_id' => $index + 1, 
	'transfer_id' => $transfer_id, 
	'candidate_id' => $candidate_id,
	'store_id' => $story_id,
	'store_name' => $faker->word,
	'company_id' => $company_id,
	'company_name' => $faker->word, 
	'company_email' => $faker->email, 
	'bank_id' => $bank_id, 
	'transfer_confirmation_id' => $faker->numberBetween(100, 200), 
	'transfer_file_id' => $transfer_file_id, 
	'transfer_benef_name' => $faker->name, 
	'transfer_benef_iban' => 'KWKW12345678123456781234567812', 
	'candidate_hourly_rate' => 1.7,
	'company_hourly_rate' => 2,
	'hours' => $hours, 
	'bonus' => 0, 
	'bonus_commission' => 0, 
	'transfer_cost' => 0, 
	'candidate_total' => $hours * 1.7, 
	'company_total' => $hours * 2,  
	'deleted' => 0, 
	'paid' => 1, 
	'tc_created_at' => $faker->date('Y-m-d H:i:s'), 
	'tc_updated_at' => $faker->date('Y-m-d H:i:s') 
]