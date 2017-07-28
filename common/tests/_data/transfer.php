<?php

return [
    //payment sent 
    [
    	'transfer_id' => 1,
    	'parent_transfer_id' => null,
    	'company_id' => 1,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 1,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    [
    	'transfer_id' => 2,
    	'parent_transfer_id' => 1,
    	'company_id' => 2,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 10,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    //locked transfers 
    [
    	'transfer_id' => 3,
    	'parent_transfer_id' => null,
    	'company_id' => 1,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 5,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    [
    	'transfer_id' => 4,
    	'parent_transfer_id' => 3,
    	'company_id' => 2,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 10,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    //Received & Distributing Salary
    [
    	'transfer_id' => 5,
    	'parent_transfer_id' => null,
    	'company_id' => 1,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 3,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    [
    	'transfer_id' => 6,
    	'parent_transfer_id' => 5,
    	'company_id' => 2,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 10,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    //locked
    [
    	'transfer_id' => 7,
    	'parent_transfer_id' => null,
    	'company_id' => 1,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 5,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    [
    	'transfer_id' => 8,
    	'parent_transfer_id' => 7,
    	'company_id' => 2,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 10,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    //draft/initiated
    [
    	'transfer_id' => 9,
    	'parent_transfer_id' => null,
    	'company_id' => 1,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 10,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    [
    	'transfer_id' => 10,
    	'parent_transfer_id' => 9,
    	'company_id' => 2,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 10,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    //payment sent
    [
    	'transfer_id' => 11,
    	'parent_transfer_id' => null,
    	'company_id' => 1,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 1,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
    [
    	'transfer_id' => 12,
    	'parent_transfer_id' => 11,
    	'company_id' => 2,
    	'total' => '75.7',
        'company_total' => '88.00',
    	'payment_received_on' => '2017-12-23',
    	'transfer_status' => 10,
    	'transfer_created_at' => '2017-02-23 18:04:42',
    	'transfer_updated_at' => '2017-02-23 18:04:42',
    	'deleted' => '0'
    ],
];