<?php

return [
    [
        'staff_name' => 'testing-staff',
        'staff_email' => 'staff@gmail.com',
        'staff_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('12345'),
    ],
    [
        'staff_name' => 'testing-staff-2',
        'staff_email' => 'staff2@gmail.com',
        'staff_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('123456'),
    ],
    [
        'staff_name' => 'testing-staff-3',
        'staff_email' => 'staff3@gmail.com',
        'staff_password_hash' => Yii::$app->getSecurity()->generatePasswordHash('1234567'),
    ],
];
