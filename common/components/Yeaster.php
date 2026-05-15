<?php

namespace common\components;

use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * component to connect to voice-mail microservice - node.js microservice that sync voice-mails by running
 * cron in background
 * node /var/www/studenthub-microservices/yeastar-voicemails/console/sync.js
 * node /var/www/studenthub-microservices/yeastar-voicemails/console/process.js
 */
class Yeaster extends \yii\base\Component
{
    /**
     * @var string|null bearer token used to authenticate calls to the voicemail microservice
     */
    public $microserviceApiKey;

    /**
     * @var string base URL for the voicemail microservice, overridden from main-local.php
     */
    public $apiEndpoint = "http://localhost:3001";

    /**
     * Build authenticated request headers and fail fast if the service token is missing.
     *
     * @return array<string, string>
     * @throws InvalidConfigException when YEASTER_MICROSERVICE_API_KEY is not configured
     */
    private function authorizationHeaders()
    {
        if (trim((string) $this->microserviceApiKey) === '') {
            throw new InvalidConfigException('YEASTER_MICROSERVICE_API_KEY must be configured before calling the voicemail microservice.');
        }

        return [
            'Authorization' => 'Bearer ' . $this->microserviceApiKey,
            'content-type' => 'application/json',
        ];
    }

    /**
     * Fetch a paginated voicemail listing from the Yeaster microservice.
     *
     * @param int $page page number to request
     * @param int $limit maximum number of voicemails to return
     * @return mixed decoded response payload
     */
    public function listVoicemails($page, $limit = 10) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/list?page=' . $page . '&limit=' . $limit)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders($this->authorizationHeaders())
            ->send();

        return $response->getData();
    }

    /**
     * Fetch a single voicemail payload from the Yeaster microservice.
     *
     * @param int|string $id voicemail identifier
     * @return string raw response content
     */
    public function viewVoicemail($id) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/view/'. $id)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders($this->authorizationHeaders())
            ->send();

        return $response->content;
    }

    /**
     * Download a voicemail recording from the Yeaster microservice.
     *
     * @param int|string $id voicemail identifier
     * @return string raw response content
     */
    public function downloadVoicemail($id) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($this->apiEndpoint . '/download/'. $id)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders($this->authorizationHeaders())
            ->send();

        return $response->content;
    }
}
