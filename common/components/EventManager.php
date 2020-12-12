<?php 
namespace common\components;

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

     /**
     * @inheritdoc
     */
    public function init()
    {
    	if ($this->key === null) {
            throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                '{class}' => static::className(),
                '{attribute}' => '$key'
            ]));
        }

        parent::init();

        $this->_client = \Mixpanel::getInstance($this->key);
    }

	/**
     * set user for trackinng/event management
     */		
    public function setUser($id, $data) 
    {
    	$ip = Yii::$app->getRequest()->getUserIP();

    	$this->_client->people->set($id, $data, $ip, $ignore_time = false);
    }

    /**
     * register event 
     */
    public function track($event, $eventData) 
    {
    	$this->_client->track($event, $eventData);
    }
}