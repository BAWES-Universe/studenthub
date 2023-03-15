<?php

namespace common\components;

use Yii;
use yii\base\Component;
use common\models\Setting;
use yii\helpers\ArrayHelper;


class Config extends Component
{
    public $data = [];

    /**
     * Sets up the Config component for use
     *
     * @param  array                           $config name-value pairs that will be used to initialize the object properties
     * @throws \yii\base\InvalidParamException if token is empty or not valid
     */
    public function __construct($config = [])
    {
        $this->load();

        parent::__construct($config);
    }

    /**
     *
     *
     * @param	string	$key
     *
     * @return	mixed
     */
    public function get(string $key): mixed  {
        return isset($this->data[$key]) ? $this->data[$key] : '';
    }

    /**
     *
     *
     * @param	string	$key
     * @param	string	$value
     */
    public function set(string $key, mixed $value): void {
        $this->data[$key] = $value;
    }

    /**
     *
     *
     * @param	string	$key
     *
     * @return	mixed
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    /**
     */
    public function load()
    {
        // Getting a list of Restaurant config
        $cacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT `updated_at` FROM setting ORDER BY `updated_at` LIMIT 1'
        ]);

        $cacheDuration = 0; //stay infinite

        $this->data = Setting::getDb()->cache(function($db) {

            $data = Setting::find()
                ->all();

            return ArrayHelper::index($data, 'key');

        }, $cacheDuration, $cacheDependency);
    }
}