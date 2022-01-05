<?php

return [
   'university_id' => $index + 1,
   'university_name_en' => $faker->name,
   'university_name_ar' => "امعة الخليج للعلوم والتكنولوجيا'",
   'university_data_source' => 1,
   'university_created_by' => null,
   'university_updated_by' => null,
   'university_created_at' => $faker->date('Y-m-d H:i:s'), 
   'university_updated_at' => $faker->date('Y-m-d H:i:s'), 
   'deleted' => '0',
];
