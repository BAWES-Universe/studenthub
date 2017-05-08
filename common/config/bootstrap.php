<?php
Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@verification', dirname(dirname(__DIR__)) . '/verification');
Yii::setAlias('@admin', dirname(dirname(__DIR__)) . '/admin');
Yii::setAlias('@candidate', dirname(dirname(__DIR__)) . '/candidate');
Yii::setAlias('@company', dirname(dirname(__DIR__)) . '/company');
Yii::setAlias('@staff', dirname(dirname(__DIR__)) . '/staff');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');

//Amazon S3 Alias
Yii::setAlias('s3','https://sh-payroll.s3.amazonaws.com');
