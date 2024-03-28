<?php

namespace common\components;

use Mpdf\Tag\P;
use Yii;
use yii\base\Component;
use yii\httpclient\Client;

/**
 * https://xeroapi.github.io/xero-php-oauth2/docs/v2/accounting/index.html#api-Accounting-getBankTransactionsHistory
 */
class Xero extends Component
{
    public $clientId;
    public $clientSecret;
    public $xeroTenantId;//tanant id for connected app

    //for oauth app
    private $token;
    private $refresh_token;
    private $expires;
    private $tenant_id;
    private $id_token;

    public function init()
    {
        parent::init();

        $session = Yii::$app->cache->get("xero-session");

        if($session) {
            $this->token = $session['token'];
            $this->expires = $session['expires'];
            $this->tenant_id = $session['tenant_id'];
            $this->refresh_token = $session['refresh_token'];
            $this->id_token = $session['id_token'];
        }
    }

    /**
     * @param $dotNetDate
     * @return \DateTime
     * @throws \Exception
     */
    function convertDotNetDate($dotNetDate) {
        // Extract the timestamp from the .NET date format
        preg_match('/\d+/', $dotNetDate, $matches);
        $timestamp = $matches[0] / 1000; // Divide by 1000 to convert milliseconds to seconds

        // Create a DateTime object using the extracted timestamp
        $dateTime = new \DateTime("@". (int) $timestamp);

        return $dateTime;
    }

    /**
     * @param $page
     * @return mixed
     */
    public function syncTransactions($page = 1, $recurring = false) {

        $result = $this->getBankTransactions($page);

        // send to mixpanel

        $dataToSave = [];

        foreach ($result as $transaction) {

            $lineItems = [];

            foreach ($transaction->getLineItems() as $lineItem) {

                $lineItems[] = [
                    'line_item_id' => $lineItem->getLineItemId(),
                    'description' => $lineItem->getDescription(),
                    'quantity' => $lineItem->getQuantity(),
                    'unit_amount' => $lineItem->getUnitAmount(),
                    'item_code' => $lineItem->getItemCode(),
                    'account_code' => $lineItem->getAccountCode(),
                    'account_id' => $lineItem->getAccountId(),
                    'tax_type' => $lineItem->getTaxType(),
                    'tax_amount' => $lineItem->getTaxAmount(),
                    'item' => $lineItem->getItem(),
                    'line_amount' => $lineItem->getLineAmount(),
                    'tracking' => $lineItem->getTracking(),
                    'discount_rate' => $lineItem->getDiscountRate(),
                    'discount_amount' => $lineItem->getDiscountAmount(),
                    'repeating_invoice_id' => $lineItem->getRepeatingInvoiceId(),
                ];
            }

            $contactObject = $transaction->getContact();

            $contact = [
                "name" => $contactObject->getName(),
                "contact_id" => $contactObject->getContactId()
            ];

            $data = [
                "type" => $transaction->getType(),
                "contact" => $contact,
                "line_items" => $lineItems,
                "bank_account" => $transaction->getBankAccount(),
                "is_reconciled"=> $transaction->getIsReconciled(),
                "date" => $this->convertDotNetDate($transaction->getDate())->format("c"),
                "reference" => $transaction->getReference(),
                "currency_code" => $transaction->getCurrencyCode(),
                "currency_rate" => $transaction->getCurrencyRate(),
                "url" => $transaction->getUrl(),
                "status" => $transaction->getStatus(),
                "line_amount_types" => $transaction->getLineAmountTypes(),
                "sub_total" => $transaction->getSubTotal(),
                "total_tax" => $transaction->getTotalTax(),
                "total" => $transaction->getTotal(),
                "bank_transaction_id" => $transaction->getBankTransactionId(),
                "prepayment_id" => $transaction->getPrepaymentId(),
                "overpayment_id" => $transaction->getOverpaymentId(),
                "updated_date_utc" => $this->convertDotNetDate($transaction->getUpdatedDateUtc())->format("c"),
                "has_attachments" => $transaction->getHasAttachments(),
                "status_attribute_string" => $transaction->getStatusAttributeString(),
                "validation_errors" => $transaction->getValidationErrors(),
            ];

            //todo: make async call as AWS have http timeout in cloudfront
            //Yii::$app->eventManager->track("Bank Transaction from Xero", $data);

            // save data in mongodb

            //$database = Yii::$app->xeroDb->getDatabase('my_mongo_db');
            //$collection = Yii::$app->xeroDb->getCollection('transactions');
            //$collection->insert(['name' => 'John Smith', 'status' => 1]);

            $dataToSave[] = $data;
        }

        // save data in mongodb

        //$collection = Yii::$app->xeroDb->getCollection('transactions');
        //$collection->batchInsert($dataToSave);

        // if having data open next page

        if(sizeof($result) > 0 && $recurring)
            return $this->syncTransactions($page + 1);

        return [
            "operation" => "success",
            "count" => sizeof($result),
            "message" => sizeof($result) . ' transaction fetched'
        ];
    }

    /**
     * @param $page
     * @param $ifModifiedSince
     * @param $where
     * @param $order
     * @return array
     */
    public function getBankTransactions($page = 1, $ifModifiedSince = null, $where = null, $order = null) {

        try {
            return $this->getClient()
                ->getBankTransactions($this->tenant_id, $ifModifiedSince, $where, $order, $page);

        } catch (Exception $e) {
            echo 'Exception when calling AccountingApi->getBankTransactions: ', $e->getMessage(), PHP_EOL;
            return [];
        }
    }

    /**
     * @param $bankTransactionID
     * @return void
     */
    public function getBankTransactionsHistory($bankTransactionID) {

        try {
            return $this->getClient()->getBankTransactionsHistory($this->tenant_id, $bankTransactionID);
        } catch (Exception $e) {
            echo 'Exception when calling AccountingApi->getBankTransactionsHistory: ', $e->getMessage(), PHP_EOL;
        }
    }

    /**
     * @return \XeroAPI\XeroPHP\Api\AccountingApi
     */
    private function getClient() {

        /*if(!$this->token) {
            $this->retriveToken();
        }*/

        // Configure OAuth2 access token for authorization: OAuth2
        $config = \XeroAPI\XeroPHP\Configuration::getDefaultConfiguration()
            ->setAccessToken( $this->token );

        return new \XeroAPI\XeroPHP\Api\AccountingApi(
            new \GuzzleHttp\Client(),
            $config
        );
    }

    /**
     * @param $url
     * @param $method
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function getResource($url, $method = "GET") {

        if(!$this->token) {
            $this->retriveToken();
        }

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod($method)
            ->setUrl($url)
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                // base64_encode($this->clientId . ":" . $this->clientSecret),
                'content-type' => 'application/json',
            ])
            ->send();

        return $response->getData();
    }

    /**
     * @return \yii\httpclient\Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function retriveToken() {

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
            $tenant_id,
            $newAccessToken->getRefreshToken(),
            $newAccessToken->getValues()["id_token"] );
    }*/

    // MANAGE SESSION

    public function setToken($token, $expires = null, $tenantId, $refreshToken, $idToken)
    {
        Yii::$app->cache->set("xero-session", [
            'token' => $token,
            'expires' => $expires,
            'tenant_id' => $tenantId,
            'refresh_token' => $refreshToken,
            'id_token' => $idToken
        ]);

        $this->token = $token;
        $this->expires = $expires;
        $this->tenant_id = $tenantId;
        $this->refresh_token = $refreshToken;
        $this->id_token = $idToken;
    }

    public function getToken()
    {
        //If it doesn't exist or is expired, return null
        if (
            empty($this->token) ||
            ($this->expires !== null && $this->expires <= time())
        ) {
            return null;
        }

        return $this->token;
    }

    public function getAccessToken()
    {
        return $this->token;
    }

    public function getRefreshToken()
    {
        return $this->refresh_token;
    }

    public function getExpires()
    {
        return $this->expires;
    }

    public function getXeroTenantId()
    {
        return $this->tenant_id;
    }

    public function getIdToken()
    {
        return $this->id_token;
    }

    public function getHasExpired()
    {
        if (!empty($this->expires))
        {
            if(time() > $this->expires)
            {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }
}