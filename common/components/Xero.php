<?php

namespace common\components;

use common\models\BankTransaction;
use common\models\BankTransactionContact;
use Mpdf\Tag\P;
use Yii;
use yii\base\Component;
use yii\db\Expression;
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
     * call syncTransactions until we sync all the transactions
     * @return void
     */
    public function syncTransactions($count = 0) {

        $order = "Date ASC";

        $ifModifiedSince = $this->getLastSyncTime();

        $result = $this->getBankTransactions(1, $ifModifiedSince, null, $order);

        $transactions = $result->getBankTransactions();

        $this->saveToDB($transactions);

        /*foreach ($transactions as $transaction) {
            $fd = $this->convertDotNetDate($transaction->getDate())->format("Y-m-d H:i:s");

            echo $transaction->getDate() . PHP_EOL .
                $fd;
        }*/

        $count += sizeof($transactions);

        // if having data open next page

        //if (sizeof($result) > 0)
        //    return $this->syncTransactions($count);

        return [
            "operation" => "success",
            "count" => $count,
            "message" => $count . ' transaction fetched'
        ];
    }

    /**
     * @return \DateTime|void
     * @throws \Exception
     */
    private function getLastSyncTime() {
        //get last transaction
        $model = BankTransaction::find()
            ->orderBy("date DESC")
            ->one();

        if ($model) {
            return new \DateTime($model->date);
        }
    }

    /**
     * Download and save to local db,page by page
     * @param $page
     * @return mixed
     */
    public function downloadTransactions($page = 1, $recurring = false, $count = 0) {

        $order = "date ASC";

        //$ifModifiedSince = null;

        $result = $this->getBankTransactions($page, null, null, $order);

        $transactions = $result->getBankTransactions();

        $this->saveToDB($transactions);

        $count += sizeof($transactions);

        // if having data open next page

        if($count > 0 && $recurring)
            return $this->downloadTransactions($page + 1, $recurring, $count);

        return [
            "operation" => "success",
            "count" => $count,
            "message" => $count . ' transaction fetched'
        ];
    }

    /**
     * save xero BankTransactions in MySql
     * @param $result
     * @return array|void
     * @throws \yii\db\Exception
     */
    private function saveToDB($result) {

        $today = (new \DateTime())->format("c");

        $bankTransactions = [];
        $bankTransactionLineItems = [];

        foreach ($result as $transaction) {

            $lineItems = [];

            foreach ($transaction->getLineItems() as $lineItem) {

                $lineItems[] = [
                    'line_item_id' => $lineItem->getLineItemId(),
                    "bank_transaction_id" => $transaction->getBankTransactionId(),
                    'description' => $lineItem->getDescription(),
                    'quantity' => $lineItem->getQuantity(),
                    'unit_amount' => $lineItem->getUnitAmount(),
                    'item_code' => $lineItem->getItemCode(),
                    'account_code' => $lineItem->getAccountCode(),
                    'account_id' => $lineItem->getAccountId(),
                    'tax_type' => $lineItem->getTaxType(),
                    'tax_amount' => $lineItem->getTaxAmount(),
                    //'item' => $lineItem->getItem(),
                    'line_amount' => $lineItem->getLineAmount(),
                    //'tracking' => $lineItem->getTracking(),
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
                "bank_transaction_id" => $transaction->getBankTransactionId(),
                "contact_id" => $contactObject->getContactId(),
                "currency_rate" => $transaction->getCurrencyRate(),
                "currency_code" => $transaction->getCurrencyCode(),
                "has_attachments" => $transaction->getHasAttachments(),
                "is_reconciled"=> $transaction->getIsReconciled(),
                "line_amount_types" => $transaction->getLineAmountTypes(),
                "overpayment_id" => $transaction->getOverpaymentId(),
                "prepayment_id" => $transaction->getPrepaymentId(),
                "reference" => $transaction->getReference(),
                "status" => $transaction->getStatus(),
                "status_attribute_string" => $transaction->getStatusAttributeString(),
                "sub_total" => $transaction->getSubTotal(),
                "total" => $transaction->getTotal(),
                "total_tax" => $transaction->getTotalTax(),
                "type" => $transaction->getType(),
                "url" => $transaction->getUrl(),
                "validation_errors" => $transaction->getValidationErrors(),
                "date" => $this->convertDotNetDate($transaction->getDate())->format("Y-m-d H:i:s"),
                //"line_items" => $lineItems,
                //"bank_account" => $transaction->getBankAccount(),
                //"updated_date_utc" => $this->convertDotNetDate($transaction->getUpdatedDateUtc())->format("Y-m-d H:i:s"),
                "created_at" => new Expression("NOW()"),
                "updated_at" => new Expression("NOW()"),
            ];

            $bankTransactions[] = $data;
            $bankTransactionLineItems = array_merge($bankTransactionLineItems, $lineItems);

            // todo: make async call as AWS have http timeout in cloudfront

            Yii::$app->eventManager->track("Bank Transaction from Xero", array_merge($data, [
                "line_items" => $lineItems,
                "date" => $this->convertDotNetDate($transaction->getDate())->format("c"),
                "contact" => $contact,
                "created_at" => $today,
                "updated_at" => $today,
            ]));

            // save data in mongodb

            //$database = Yii::$app->xeroDb->getDatabase('my_mongo_db');
            //$collection = Yii::$app->xeroDb->getCollection('transactions');
            //$collection->insert(['name' => 'John Smith', 'status' => 1]);

            /*$arrContacts[] = $contact;
            $arrBankTransactions[] = $data;
            $data;*/

            $contactModel = BankTransactionContact::find()
                ->andWhere(['contact_id' => $contact['contact_id']])
                ->exists();

            if(!$contactModel) {
                $contactModel = new BankTransactionContact();
                $contactModel->setAttributes($contact);
                if (!$contactModel->save()) {
                    return [
                        "operation" => "error",
                        "message" => $contactModel->errors
                    ];
                }
            }

            /*$model = new BankTransaction();
            $model->setAttributes($data);

            if(!$model->save()) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }

            return [
                "operation" => "success",
            ];*/
        }

        Yii::$app->db->createCommand()->batchInsert('bank_transaction',
            ['bank_transaction_id', 'contact_id', 'currency_rate', 'currency_code', 'has_attachments', 'is_reconciled',
                'line_amount_types', 'overpayment_id', 'prepayment_id', 'reference', 'status', 'status_attribute_string',
                'sub_total', 'total', 'total_tax', 'type', 'url', 'validation_errors', 'date', 'created_at', 'updated_at'],
            $bankTransactions
        )->execute();

        Yii::$app->db->createCommand()->batchInsert('bank_transaction_line_item',
            ['line_item_id', 'bank_transaction_id', 'description', 'quantity',
                'unit_amount', 'item_code', 'account_code',
                'account_id', 'tax_type', 'tax_amount', 'line_amount', 'discount_rate',
                'discount_amount', 'repeating_invoice_id'],
            $bankTransactionLineItems
        )->execute();
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