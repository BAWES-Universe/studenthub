<?php

namespace common\components;

use yii\httpclient\Client;

class Yeaster
{
    public $microserviceApiKey = "QstN8_18LmILpl37r2zvdDCp5JjWPCNh";

    public $apiEndpoint = "http://localhost:3001";

    public function listVoicemails($page, $limit = 10) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/list?page=' . $page . '&limit=' . $limit)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders([
                'Authorization' => 'Bearer ' . $this->microserviceApiKey,
                'content-type' => 'application/json',
            ])
            ->send();

        return $response->getData();
    }

    public function viewVoicemail($id) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/view/'. $id)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders([
                'Authorization' => 'Bearer ' . $this->microserviceApiKey,
                'content-type' => 'application/json',
            ])
            ->send();

        return $response->content;
    }

    public function downloadVoicemail($id) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/download/'. $id)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders([
                'Authorization' => 'Bearer ' . $this->microserviceApiKey,
                'content-type' => 'application/json',
            ])
            ->send();

        return $response->content;
    }
}