<?php

return [
    [
    	'company_id' => 1,
    	'parent_company_id' => NULL,
    	'company_name' => 'First Company - Parent',
    	'company_email' => 'company@company.com',
    	'company_auth_key' => 'f71hCXxe42UhlWuN_dbpCE5TcX7qN_vL',
    	'company_password_hash' => '$2y$13$I/rXoGKXIIyqyaByFXdl8.b3RBywm.oNLquFQYdnaulxwXO.1d4va',
    	//'company_password_reset_token' => '',
    	'company_status' => 10,
    	'company_created_at' => '2017-02-23 18:04:42',
    	'company_updated_at' => '2017-02-23 18:04:42'
    ],
    [
    	'company_id' => 2,
    	'parent_company_id' => 1,
    	'company_name' => 'Second Company - Child',
    	'company_email' => '',
    	'company_auth_key' => '',
    	'company_password_hash' => '$2y$13$I/rXoGKXIIyqyaByFXdl8.b3RBywm.oNLquFQYdnaulxwXO.1d4va',
    	//'company_password_reset_token' => '',
    	'company_status' => 10,
    	'company_created_at' => '2017-02-23 18:04:42',
    	'company_updated_at' => '2017-02-23 18:04:42'
    ],
    [
    	'company_id' => 3,
    	'parent_company_id' => null,
    	'company_name' => 'Without Child',
    	'company_email' => 'company3@bawes.net',
    	'company_auth_key' => '',
    	'company_password_hash' => '$2y$13$I/rXoGKXIIyqyaByFXdl8.b3RBywm.oNLquFQYdnaulxwXO.1d4va',
    	//'company_password_reset_token' => '',
    	'company_status' => 10,
    	'company_created_at' => '2017-02-23 18:04:42',
    	'company_updated_at' => '2017-02-23 18:04:42'
    ]
];