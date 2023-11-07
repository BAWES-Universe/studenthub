<?php 
namespace common\components;

use common\models\Webhook;
use Segment\Segment;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

class EventManager extends Component
{
	private $_client;

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

            $this->key = Yii::$app->config->get('Mixpanel-Key');

            $this->_client = \Mixpanel::getInstance($this->key);
        }

        if($this->segmentStatus) {

            $this->segmentKey = Yii::$app->config->get('Segment-Key');
            $this->walletSegmentKey = Yii::$app->config->get('Segment-Key-Wallet');

            if($this->segmentKey)
                Segment::init($this->segmentKey);
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

        if($this->_client)
    	    $this->_client->people->set($id, $data, $ip, $ignore_time = false);

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
        if($this->_client) {

            $mixpanelData = $eventData;

            if($timestamp) {
                $mixpanelData =  array_merge([
                    "\$time" => strtotime($timestamp),
                    "\$created" => $timestamp,
                ], $eventData);
            }

            if($userId)
                $mixpanelData['$distinct_id'] = $userId;

            $this->_client->track($event, $mixpanelData);
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