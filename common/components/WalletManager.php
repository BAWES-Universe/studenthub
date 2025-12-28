<?php

namespace common\components;

use yii\httpclient\Client;

class WalletManager extends \yii\base\Component
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
    public function addEntry($data)
    {
        // WALLET DISABLED (LEGACY FEATURE):
        // The wallet integration is no longer used.
        // We keep this method as a no-op so existing calls don't break,
        // but we do NOT call the external wallet service anymore.

        \Yii::info(
            'WalletManager::addEntry called but wallet is disabled. Payload: ' . print_r($data, true),
            __METHOD__
        );

        // Always signal success so calling code (paymentReceived, markPaid, markAllPaid)
        // can proceed without being blocked.
        return ['operation' => 'success'];

        /*
        // Legacy implementation kept for reference:

        $client = new Client();
        try {
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

            if ($response->isOk) {
                return ["operation" => "success"];
            } else {
                return [
                    "operation" => "error",
                    "message" => isset($response->data['message']) ? $response->data['message'] : $response->content,
                ];
            }
        } catch (\Exception $e) {
            \Yii::error($e->getMessage());

            return [
                "operation" => "error",
                "message" => $e->getMessage(),
            ];
        }
        */
    }
}
