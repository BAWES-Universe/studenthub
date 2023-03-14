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

    private $segmentKey;

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

        if($this->key)
            $this->_client = \Mixpanel::getInstance($this->key);
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