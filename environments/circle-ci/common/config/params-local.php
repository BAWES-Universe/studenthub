<?php
return [
    'allowedOrigins' => [
        //Dev Envs
        '*' //remove this once app is live
    ],
    'algolia_candidate_index' => 'test_candidate_public',
    'algolia_fulltimer_index' => 'test_fulltimer_public',
    'meilisearch_master_key' => getenv('MEILI_MASTER_KEY') ?: 'changeme_master_key_please_set_in_env',
    'meilisearch_candidate_index' => 'test_candidate_public',
    'meilisearch_fulltimer_index' => 'test_fulltimer_public',
    'meilisearch_host' => 'http://meilisearch:7700',
    'oneSignalCandidateAPPID' => 'fe766231-6156-4537-8037-84e3fe1be5da',
    'oneSignalCandidateAPIKey' => 'YTBkODdlMjctOGQ0Ny00NDgwLTkyMmYtOWQ1NTI5ODlmZjY1',
    'finance_transfer' => 'finance+fake@bawes.net',
    'candidateAppUrl' => 'https://student.dev.studenthub.co/',
    'inspectorAppUrl' => 'https://inspector.dev.studenthub.co/',
    'statusAppUrl' => 'https://status.dev.studenthub.co/',
    'companyAppUrl' => 'https://employer.dev.studenthub.co/',
    "managerAppUrl" => 'https://manager.dev.studenthub.co/',
    'staffAppUrl' => 'https://staff.dev.studenthub.co/',
    'adminAppUrl' => 'https://admin.dev.studenthub.co/',
];
