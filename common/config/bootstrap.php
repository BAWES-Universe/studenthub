<?php
// Load environment variables from .env file
if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

// Load environment-specific .env file if exists
$envFile = __DIR__ . '/../../environments/' . (defined('YII_ENV') ? YII_ENV : 'dev') . '/.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname($envFile));
    $dotenv->load();
}

Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@verification', dirname(dirname(__DIR__)) . '/verification');
Yii::setAlias('@admin', dirname(dirname(__DIR__)) . '/admin');
Yii::setAlias('@candidate', dirname(dirname(__DIR__)) . '/candidate');
Yii::setAlias('@company', dirname(dirname(__DIR__)) . '/company');
Yii::setAlias('@manager', dirname(dirname(__DIR__)) . '/manager');
Yii::setAlias('@staff', dirname(dirname(__DIR__)) . '/staff');
Yii::setAlias('@inspector', dirname(dirname(__DIR__)) . '/inspector');
Yii::setAlias('@status', dirname(dirname(__DIR__)) . '/status');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');
Yii::setAlias('@bower', dirname(dirname(__DIR__)) . '/vendor/bower');
Yii::setAlias('@npm', dirname(dirname(__DIR__)) . '/vendor/npm-asset');