<?php
return [
    'allowedOrigins' => [
        //Dev Envs
        '*' //remove this once app is live
    ],
    'algolia_candidate_index' => 'krushn_candidate_public',
    'algolia_fulltimer_index' => 'krushn_fulltimer_public',
    'oneSignalCandidateAPPID' => getenv('ONESIGNAL_CANDIDATE_APP_ID') ?: '',
    'oneSignalCandidateAPIKey' => getenv('ONESIGNAL_CANDIDATE_API_KEY') ?: '',
    'finance_transfer' => 'finance+fake@bawes.net',
    'candidateAppUrl' => 'https://student.dev.studenthub.co/',
    'inspectorAppUrl' => 'https://inspector.dev.studenthub.co/',
    'statusAppUrl' => 'https://status.dev.studenthub.co/',
    'companyAppUrl' => 'https://employer.dev.studenthub.co/',
    "managerAppUrl" => 'https://manager.dev.studenthub.co/',
    'staffAppUrl' => 'https://staff.dev.studenthub.co/',
    'adminAppUrl' => 'https://admin.dev.studenthub.co/',
];
