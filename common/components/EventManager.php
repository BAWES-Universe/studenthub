<?php 
namespace common\components;

use common\models\Webhook;
use Segment\Segment;
use Yii;
use yii\base\Component;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;
use yii\httpclient\Client;

class EventManager extends Component
{
    /**
     * @var string Mixpanel key
     */
	private $_client;

    /**
     * @var string Mixpanel key for BAWES project
     */
    private $_walletClient;

    /**
     * @var string Mixpanel key for BAWES project
     */
    private $_sqsClient;

    /**
     * @var string AWS SQS Region
     */
    public $sqsRagion;

    /**
     * @var string AWS SQS key
     */
    public $sqsKey;

    /**
     * @var string AWS SQS secret
     */
    public $sqsSecret;

    /**
     * @var string AWS SQS queue
     */
    public $sqsQueue;

    /**
     * @var string AWS SQS endpoint
     */
    public $sqsEndpoint;

	/**
     * @var string Mixpanel key
     */
    public $key;

    /**
     * @var string | null Mixpanel key for BAWES project
     */
    public $walletMixpanelKey;

    /**
     * @var string | null Mixpanel status
     */
    public $mixpanelStatus;

    /**
     * @var string | null Segment status
     */
    public $segmentStatus;

    /**
     * @var string | null Segment key
     */
    public $segmentKey;

    /**
     * @var string | null Segment key for wallet app
     */
    public $walletSegmentKey;

    /**
     * @var boolean Whether segment identity defined
     */
    private $segmentIdentify;

    /**
     * @var array Wallet events
     */
    public $walletEvents = [
        "Candidate Transfer Paid"
    ];

     /**
     * @inheritdoc
     */
    public function init()
    {
    	/*if ($this->key === null) {
            throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                '{class}' => static::class,
                '{attribute}' => '$key'
            ]));
        }*/

        parent::init();

        //'key' => 'bfe2ac5e039a3d8d1c8e281967d6f954',//test: ac62dbe81767f8871f754c7bdf6669d6
        //'segmentKey' => 'WZc7uvfkM1uhsjT1Eie6PONXFZK3ME15'//test: 7oEpdGxjwBMlwBQYuXD7NpYWp4HzDJWh
        //'walletSegmentKey' => 'j18MpMF6fvZzmc6bvF0VjlTajAlKwai2'//test: 7oEpdGxjwBMlwBQYuXD7NpYWp4HzDJWh

        $this->segmentStatus = Yii::$app->config->get('Segment-Status');
        $this->mixpanelStatus = Yii::$app->config->get('Mixpanel-Status');

        if($this->mixpanelStatus) {

            if (YII_ENV == 'prod') {
                $this->key = Yii::$app->config->get('Mixpanel-Key');
                $this->walletMixpanelKey = Yii::$app->config->get('Mixpanel-Key-Wallet');
            } else {
                $this->key = Yii::$app->config->get('Test-Mixpanel-Key');
                $this->walletMixpanelKey = Yii::$app->config->get('Test-Mixpanel-Key-Wallet');
            }

            $this->_client = \Mixpanel::getInstance($this->key);

            if ($this->walletMixpanelKey) {
                $this->_walletClient = \Mixpanel::getInstance($this->walletMixpanelKey);
            }
        }

        if($this->segmentStatus) {

            if (YII_ENV == 'prod') {
                $this->segmentKey = Yii::$app->config->get('Segment-Key');
                $this->walletSegmentKey = Yii::$app->config->get('Segment-Key-Wallet');
            } else {
                $this->segmentKey = Yii::$app->config->get('Test-Segment-Key');
                $this->walletSegmentKey = Yii::$app->config->get('Test-Segment-Key-Wallet');
            }

            if($this->segmentKey)
                Segment::init($this->segmentKey);
        }

        // Create an SQS client
        if ($this->sqsSecret && $this->sqsKey && $this->sqsRagion)
        {
            $this->_sqsClient = new SqsClient([
                'region' => $this->sqsRagion, // Replace with your region
                'version' => 'latest',
                'credentials' => [
                    'key'    => $this->sqsKey, // Replace with your access key
                    'secret' => $this->sqsSecret, // Replace with your secret key
                ],
            ]);
        }
    }

    /**
     * init segment for tracking/event management
     */
    public function initSegment($key) {

        $this->segmentKey = $key;

        Segment::init($key);
    }

	/**
     * set user for trackinng/event management
     */		
    public function setUser($id, $data) 
    {
        try {
            $ip = Yii::$app->getRequest()->getUserIP();
        } catch (yii\base\UnknownMethodException $exception) {
            $ip = "192.168.0.1";
        }

        if($this->_client) {
            $this->_client->identify($id);
            $this->_client->people->set($id, $data, $ip, $ignore_time = false);
        }

        if ($this->_walletClient) {
            $this->_walletClient->identify($id);
            $this->_walletClient->people->set($id, $data, $ip, $ignore_time = false);
        }

        if($this->segmentKey) {
            Segment::identify([
                "userId" => $id,
                "traits" => $data
            ]);

            $this->segmentIdentify = true;
        }
    }

    /**
     * register event 
     */
    public function track($event, $eventData, $timestamp = null, $userId = null, $onlyWallet = false)
    {
        $distinctID = null;

        if(isset(Yii::$app->request) && Yii::$app->request instanceof \yii\web\Request) {
            $distinctID = Yii::$app->request->headers->get('Mixpanel-Distinct-ID');
        }

        $language = Yii::$app->language == "ar"? "ar": "en";

        $eventData = array_merge($eventData, [
            "language" => $language,
        ]);

        //if login

        if(isset(Yii::$app->user) && !Yii::$app->user->isGuest) {

            //if admin login

            if(isset(Yii::$app->user->identity->admin_name)) {
                $eventData["channel"] = "Admin Web App";
            }
            else if(isset(Yii::$app->user->identity->candidate_name)) {
                $eventData["channel"] = "Candidate Web App";
            }
            else if(isset(Yii::$app->user->identity->contact_name))
            {
                $eventData["channel"] = "Company Web App";
            }
            else if(isset(Yii::$app->user->identity->inspector_name))
            {
                $eventData["channel"] = "Inspector Web App";
            }
            else if(isset(Yii::$app->user->identity->staff_name))
            {
                $eventData["channel"] = "Staff Web App";
            }

            if(!$userId)
                $userId = Yii::$app->user->getId();
        }

        if(empty($eventData["channel"])) {
            $eventData["channel"] = "Backend";
        }

        if($this->_client) {

            $mixpanelData = $eventData;

            if($timestamp) {
                $mixpanelData =  array_merge([
                    "\$time" => strtotime($timestamp),
                    "\$created" => $timestamp,
                    "time" => strtotime($timestamp),
                ], $eventData);
            }

            if($distinctID) {
                $mixpanelData['$distinct_id'] = $distinctID;
            }

            if($userId) {
                $mixpanelData['$user_id'] = $userId;
                $mixpanelData['user_id'] = $userId;

                if(empty($mixpanelData['$distinct_id'])) {
                    $mixpanelData['$distinct_id'] = $userId;
                }
            } else if (isset($mixpanelData['$distinct_id'])) {
                $mixpanelData['$user_id'] = $mixpanelData['$distinct_id'];
            }

            //to fix: not showing in listing but in detail view in mixpanel

            if(isset($mixpanelData['$distinct_id']))
                $mixpanelData['distinct_id'] = $mixpanelData['$distinct_id'];

            if($userId)
                $mixpanelData['$distinct_id'] = $userId;

            //if wallet event, send to wallet/ main project
            if ($this->_walletClient && in_array($event, $this->walletEvents)) {

                $this->_walletClient->track("Revenue", $mixpanelData);
                $this->_walletClient->flush();

                if ($onlyWallet)
                    return true;
            }

            $this->_client->track($event, $mixpanelData);

            //to fix order
            $this->_client->flush();
        }

        if($this->segmentKey) {

            $data = [
                'event' => $event,
                'properties' => $eventData,
                'timestamp' => $timestamp,
            ];

            //if login and userId not provided

            if(is_null($userId) && isset(Yii::$app->user) && !Yii::$app->user->isGuest) {
                $userId = Yii::$app->user->getId();
            }

            if(!$userId) {
                $userId = "anonymous";
            }

            if($this->segmentIdentify)  {
                $data['userId'] = $userId;
            } else {
                $data['anonymousId'] = $userId;
            }

            Segment::track($data);

            Segment::flush();
        }

        // send to queue

        if ($this->sqsQueue) {


            //if login and userId not provided

            if(is_null($userId) && isset(Yii::$app->user) && !Yii::$app->user->isGuest) {
                $userId = Yii::$app->user->getId();
            }

            if(!$userId) {
                $userId = "anonymous";
            }

            $data = array_merge($eventData, [
                "login_user_id" => $userId
            ]);

            /* Time taken: 0.6483371257782 seconds
             * ------------------------------------------------*/
            if ($this->_sqsClient) {
                try {

                    $queueUrl = 'https://sqs.' . $this->sqsRagion . '.amazonaws.com/' . $this->sqsQueue; // Replace with your queue URL

                    $this->_sqsClient->sendMessage([
                        'QueueUrl' => $queueUrl,
                        'MessageBody' => json_encode($data),
                    ]);

                    //Yii::debug("Message sent! Message ID: " . $result->get("MessageId"));


                } catch (AwsException $e) {
                    Yii::debug("Error sending message: " . $e->getMessage());
                }
            }

            // Time taken: 0.080348968505859 seconds
            if ($this->sqsEndpoint) {
                $this->call("POST", $this->sqsEndpoint . "/send", [
                    "message" => $data,
                    "queue" => $this->sqsQueue
                ]);

                //$result = json_decode($response->content);
            }
        }

        //find webhook for this event and fire

        $webhooks = Webhook::findAll(['event' => $event]);

        foreach ($webhooks as $webhook) {
            $webhook->callWebhook($eventData);
        }
    }

    /**
     * API call for webhook
     */
    public function call($method, $url, $data = []) {
        $client = new Client();

        return $client->createRequest()
            ->setMethod($method)
            ->setUrl($url)
            ->setFormat(Client::FORMAT_JSON)
            ->setData($data)
            ->addHeaders([
                'Authorization' =>'Bearer QstN8_18LmILpl37r2zvdDCp5JjWPCNh',
                "Content-Type" => "application/json",
                'User-Agent' => 'request',
            ])
            ->send();
    }

    /**
     * flush events
     */
    public function flush()
    {
        if($this->segmentKey)
            Segment::flush();
    }
}