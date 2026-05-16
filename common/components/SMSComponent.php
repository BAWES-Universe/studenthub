<?php
namespace common\components;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * SmsComponent class to send SMS
 */
class SMSComponent extends Component
{
    /**
     * @var string SMS provider endpoint URL
     */
    public $apiEndpoint;

    /**
     * @var string SMS provider username
     */
    public $username;

    /**
     * @var string SMS provider password
     */
    public $password;

    /**
     * @var string SMS sender name
     */
    public $sender;

    /**
     * @var string SMS provider language flag
     */
    public $language = 'L';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        foreach (['apiEndpoint', 'username', 'password', 'sender'] as $attribute) {
            if (!is_string($this->$attribute) || trim($this->$attribute) === '') {
                throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::class,
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }

        if (stripos($this->apiEndpoint, 'https://') !== 0) {
            throw new InvalidConfigException(strtr('"{class}::{attribute}" must use HTTPS.', [
                '{class}' => static::class,
                '{attribute}' => '$apiEndpoint'
            ]));
        }
    }

    /**
     * Send SMS
     */
    public function sendSms($phone_number, $message)
    {
        //$phone_number = str_replace('+', '', $phone_number);
        $phone_number = str_replace(' ', '', $phone_number);

        $smsParams = [
            "UID" => $this->username,
            "p" => $this->password,
            "S" => $this->sender,
            "G" => $phone_number,
            "M" => $message,
            "L" => $this->language,
        ];

        $client = new Client();

        return $client->createRequest()
            ->setMethod('POST')
            ->setUrl($this->apiEndpoint)
            ->setData($smsParams)
            ->setOptions(['timeout' => 10])
            ->send();
    }
}
