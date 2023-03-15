<?php 
namespace common\components;

use Segment\Segment;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

class EventManager extends Component
{
	private $_client;

	/**
     * @var string Amazon access key
     */
    public $key;

    public $segmentKey;

    public $walletSegmentKey;

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
    	$ip = Yii::$app->getRequest()->getUserIP();

        if($this->_client)
    	    $this->_client->people->set($id, $data, $ip, $ignore_time = false);

        if($this->segmentKey)
            Segment::identify([$id, data]);
    }

    /**
     * register event 
     */
    public function track($event, $eventData, $timestamp = null, $userId = null)
    {
        if($this->_client)
            $this->_client->track($event, $eventData);

        if($this->segmentKey) {

            if(is_null($userId))
                $userId = Yii::$app->user->getId();

            $data = [
                'event' => $event,
                'properties' => $eventData,
                'timestamp' => $timestamp
            ];

            if(Yii::$app->user->isGuest)  {
                $data['anonymousId'] = $userId;
            } else {
                $data['userId'] = $userId;
            }

            Segment::track($data);
        }
    }

    public function flush()
    {
        if($this->segmentKey)
            Segment::flush();
    }
}