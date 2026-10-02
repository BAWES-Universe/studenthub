<?php
return [
    'allowedOrigins' => [
        //Dev Envs
        '*' //remove this once app is live
    ],
    'algolia_candidate_index' => 'prod_candidate_public',
    'algolia_fulltimer_index' => 'prod_fulltimer_public',
    'oneSignalCandidateAPPID' => getenv('ONESIGNAL_CANDIDATE_APP_ID') ?: '',
    'oneSignalCandidateAPIKey' => getenv('ONESIGNAL_CANDIDATE_API_KEY') ?: '',
    'finance_transfer' => 'finance@bawes.net',
    'candidateAppUrl' => 'https://student.studenthub.co/',
    'inspectorAppUrl' => 'https://inspector.studenthub.co/',
    'statusAppUrl' => 'https://status.studenthub.co/',
    'companyAppUrl' => 'https://employer.studenthub.co/',
    "managerAppUrl" => 'https://manager.studenthub.co/',
    'staffAppUrl' => 'https://staff.studenthub.co/',
    'adminAppUrl' => 'https://admin.studenthub.co/',
];
