<?php

return [
    'invitation_uuid' => 'invitation_' .$faker->uuid,
    'candidate_id' => $index + 1,
    'request_uuid' => $index + 1,
    'invitation_status' => $faker->numberBetween(0,3),
    'invitation_created_by_staff' => $index + 1,
    'invitation_updated_by_staff' => $index + 1,
    'invitation_created_by_company' => $index + 1,
    'invitation_updated_by_company' => $index + 1,
    'invitation_created_at' =>  $faker->date('Y-m-d H:i:s'),
    'invitation_updated_at' => $faker->date('Y-m-d H:i:s')
];
