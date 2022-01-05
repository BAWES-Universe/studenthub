<?php 

return [
	'request_checklist_uuid' => 'request_checklist_'.$faker->uuid,  
	'status_name' => $faker->word,
	'status_name_ar' => $faker->word, 
	'is_require' => 0,
	'sort_order' => 0,
	'created_at' => $faker->date('Y-m-d H:i:s'),
	'updated_at' => $faker->date('Y-m-d H:i:s'),
]