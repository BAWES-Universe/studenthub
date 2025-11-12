<?php
return [
    'allowedOrigins' => '*', // Dev Envs - allow all origins (remove this once app is live)
    'algolia_candidate_index' => 'dev_candidate_public',
    'algolia_fulltimer_index' => 'dev_fulltimer_public',
    'meilisearch_master_key' => getenv('MEILI_MASTER_KEY') ?: 'test_master_key_12345',
    'meilisearch_candidate_index' => 'dev_candidate_public',
    'meilisearch_fulltimer_index' => 'dev_fulltimer_public',
    'meilisearch_host' => 'http://meilisearch:7700',
    'oneSignalCandidateAPPID' => 'fe766231-6156-4537-8037-84e3fe1be5da',
    'oneSignalCandidateAPIKey' => 'YTBkODdlMjctOGQ0Ny00NDgwLTkyMmYtOWQ1NTI5ODlmZjY1',
    'finance_transfer' => 'finance+fake@bawes.net',
    'candidateAppUrl' => 'http://candidate.studenthub.local/',
    'inspectorAppUrl' => 'http://inspector.studenthub.local/',
    'statusAppUrl' => 'http://status.studenthub.local/',
    'companyAppUrl' => 'http://employer.studenthub.local/',
    "managerAppUrl" => 'http://manager.studenthub.local/',
    'staffAppUrl' => 'http://staff.studenthub.local/',
    'adminAppUrl' => 'http://admin.studenthub.local/',
];
