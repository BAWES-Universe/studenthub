<?php

namespace common\components;

use yii\httpclient\Client;

class WalletManager
{
    public $apiKey;

    public $companyWalletUserID = "user_8980819c-7a02-11ed-9517-069cd3c849a2";

    public $apiEndpoint = "https://webhook.wallet.bawes.net/v1";

    /**
     * add new wallet entry
     * @param $data [number amount, string data, string tagNames, string user_uuid]
     * @return \yii\httpclient\Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function addEntry($data) {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl($this->apiEndpoint . '/balance/add-wallet-entry')
            ->setFormat(Client::FORMAT_JSON)
            ->setData($data)
            ->addHeaders([
                'x-api-key' => $this->apiKey,
                'Authorization' => 'Bearer ' . $this->apiKey,
                'content-type' => 'application/json',
            ])
            ->send();

        return $response;
    }
}