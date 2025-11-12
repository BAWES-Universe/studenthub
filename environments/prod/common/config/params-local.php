<?php
return [
    'allowedOrigins' => [
        //Dev Envs
        '*' //remove this once app is live
    ],
    'algolia_candidate_index' => 'prod_candidate_public',
    'algolia_fulltimer_index' => 'prod_fulltimer_public',
    'meilisearch_master_key' => getenv('MEILI_MASTER_KEY') ?: '',
    'meilisearch_candidate_index' => 'prod_candidate_public',
    'meilisearch_fulltimer_index' => 'prod_fulltimer_public',
    'meilisearch_host' => 'http://meilisearch:7700',
    'oneSignalCandidateAPPID' => '265d4bf5-5333-445d-8fba-08f1c389aa5f',
    'oneSignalCandidateAPIKey' => 'ZmY3OWFlMzAtN2VjNS00OWMxLTgwOWQtYjA2MDUyMzQxM2Y5',
    'finance_transfer' => 'finance@bawes.net',
    'candidateAppUrl' => 'https://student.studenthub.co/',
    'inspectorAppUrl' => 'https://inspector.studenthub.co/',
    'statusAppUrl' => 'https://status.studenthub.co/',
    'companyAppUrl' => 'https://employer.studenthub.co/',
    "managerAppUrl" => 'https://manager.studenthub.co/',
    'staffAppUrl' => 'https://staff.studenthub.co/',
    'adminAppUrl' => 'https://admin.studenthub.co/',
];
