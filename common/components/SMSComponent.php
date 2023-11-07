<?php
namespace common\components;

use yii\base\Component;
use yii\httpclient\Client;

/**
 * SmsComponent class to send SMS
 */
class SMSComponent extends Component
{
    /**
     * @var string Variable for test api key to be stored in
     */
    private $apiEndpoint = "http://62.215.226.164/fccsms.aspx";

    /**
     * Send SMS
     */
    public function sendSms($phone_number, $message)
    {
        //$phone_number = str_replace('+', '', $phone_number);
        $phone_number = str_replace(' ', '', $phone_number);

        $smsParams = [
            "UID" => "usrbawes",
            "p" => "bawes1452",
            "S" => "Plugn",
            "G" => $phone_number,
            "M" => $message,
            "L" => "L",
        ];

        $client = new Client();

        return $client->createRequest()
            ->setMethod('POST')
            ->setUrl($this->apiEndpoint)
            ->setData($smsParams)
            ->send();
    }
}
