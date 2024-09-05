<?php 
namespace common\components;

use common\models\Webhook;
use Segment\Segment;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;


class EventManager extends Component
{
	private $_client;
    private $_sqsClient;

    public $sqsRagion;
    public $sqsKey;
    public $sqsSecret;
    public $sqsQueue;

	/**
     * @var string Mixpanel key
     */
    public $key;

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
     * @inheritdoc
     */
    public function init()
    {
    	/*if ($this->key === null) {
            throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                '{class}' => static::className(),
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
            } else {
                $this->key = Yii::$app->config->get('Test-Mixpanel-Key');
            }

            $this->_client = \Mixpanel::getInstance($this->key);
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
    public function track($event, $eventData, $timestamp = null, $userId = null)
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

        if ($this->_sqsClient && $this->sqsQueue) {
            $queueUrl = 'https://sqs.' . $this->sqsRagion . '.amazonaws.com/' . $this->sqsQueue; // Replace with your queue URL

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

            try {
                $result = $this->_sqsClient->sendMessage([
                    'QueueUrl' => $queueUrl,
                    'MessageBody' => json_encode($data),
                ]);

                Yii::debug("Message sent! Message ID: " . $result->get('MessageId'));

            } catch (AwsException $e) {
                echo $e->getMessage();
                Yii::debug("Error sending message: " . $e->getMessage());
            }
        }

        //find webhook for this event and fire

        $webhooks = Webhook::findAll(['event' => $event]);

        foreach ($webhooks as $webhook) {
            $webhook->callWebhook($eventData);
        }
    }

    public function flush()
    {
        if($this->segmentKey)
            Segment::flush();
    }
}