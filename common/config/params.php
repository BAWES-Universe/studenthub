<?php
return [
    'appName' => 'StudentHub',
    'invoiceFrom' => 'khalid@bawes.net',
    'invoiceCC' => 'finance@bawes.net',
    'adminEmail' => 'khalid@studenthub.co',
    'supportEmail' => 'contact@studenthub.co',
    'operationsEmail' => 'operations@studenthub.co',
    'recruitmentEmail' => "recruitment@bawes.net",
    'accountManagerEmail' => "naif@studenthub.co",
    'user.passwordResetTokenExpire' => 3600,
    'transfer_cost' => 0,
    'salaryDay' => 5, //salary should get transfer by 5th day of every month
    'payment_notice_period' => '-35 days',
    'candidate_photo' => 'https://res.cloudinary.com/studenthub/image/upload/v1596525812/',
    'google_api_key' => 'AIzaSyBSM8o4WSIIRn-sNhn-PvO2s0ovZuLDAaw',
    'mailThreshold' => 500,
    "aws_temp_access_key_id" => getenv('AWS_TEMP_BUCKET_KEY') ?: '',
    "aws_temp_secret_access_key" => getenv('AWS_TEMP_BUCKET_SECRET') ?: '',
    "elasticMailIpPool" => "Default",
    'bankInfo' => [ //BAWES Bank Info
        'accountName' => 'BAWES FOR COMPUTER AND OPERATION COMPANY',
        'accountNameArabic' => "شركة باوس لبرمجة وتشغيل الكمبيوتر وتصميم وإدارة مواقع الانترنت",
        'bankName' => 'AI AHLI BANK OF KUWAIT - Head Office Branch',
        'bankNameArabic' => "",
        'swiftCode' => 'ABKKKWKWXXX',
        'accountNumber' => '0603022881001',
        'iban' => 'KW50 ABKK 0000 0000 0060 3022 881001'
    ]
];
