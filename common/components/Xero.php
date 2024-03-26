<?php

namespace common\components;

use yii\httpclient\Client;

/**
 * https://xeroapi.github.io/xero-php-oauth2/docs/v2/accounting/index.html#api-Accounting-getBankTransactionsHistory
 */
class Xero
{
    public $clientId;
    public $clientSecret;
    public $xeroTenantId;

    private $token;

    public function syncTransactions($page = 1) {
        $result = $this->getBankTransactions($page);

        //save data in mongodb



        //if having data open next page

        $this->syncTransactions(page + 1);
    }

    public function getBankTransactions($page = 1, $ifModifiedSince = null, $where = null, $order = null) {

        try {
            $result = $this->getClient()
                ->getBankTransactions($this->xeroTenantId, $ifModifiedSince, $where, $order, $page);
            //$unitdp

            print_r($result);
        } catch (Exception $e) {
            echo 'Exception when calling AccountingApi->getBankTransactions: ', $e->getMessage(), PHP_EOL;
        }
    }

    public function getBankTransactionsHistory($bankTransactionID) {

        try {
            $result = $this->getClient()->getBankTransactionsHistory($this->xeroTenantId, $bankTransactionID);
        } catch (Exception $e) {
            echo 'Exception when calling AccountingApi->getBankTransactionsHistory: ', $e->getMessage(), PHP_EOL;
        }
    }

    private function getClient() {

        if(!$this->token) {
            $this->getToken();
        }

        // Configure OAuth2 access token for authorization: OAuth2
        $config = XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()
            ->setAccessToken( $this->token );

        return new XeroAPI\XeroPHP\Api\AccountingApi(
            new GuzzleHttp\Client(),
            $config
        );
    }

    public function getToken() {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('POST')
            ->setUrl("https://identity.xero.com/connect/token?grant_type=client_credentials&scope=accounting.transactions,accounting.contacts")
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders([
                'Authorization' => 'Bearer ' . base64_encode($this->clientId . ":" . $this->clientSecret),
                'content-type' => 'application/json',
            ])
            ->send();

        //print_r($response);
        $this->token = $response->data->access_token;

        return $response;
    }

    /*
    private function refreshToken() {
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => '__YOUR_CLIENT_ID__',
            'clientSecret'            => '__YOUR_CLIENT_SECRET__',
            'redirectUri'             => 'http://localhost:8888/xero-php-oauth2-starter/callback.php',
            'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
            'urlAccessToken'          => 'https://identity.xero.com/connect/token',
            'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Organisation'
        ]);

        $newAccessToken = $provider->getAccessToken('refresh_token', [
            'refresh_token' => $storage->getRefreshToken()
        ]);

        // Save my token, expiration and refresh token
        $storage->setToken(
            $newAccessToken->getToken(),
            $newAccessToken->getExpires(),
            $xeroTenantId,
            $newAccessToken->getRefreshToken(),
            $newAccessToken->getValues()["id_token"] );
    }*/
}