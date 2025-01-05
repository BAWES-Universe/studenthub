<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\httpclient\Client;

/**
 * This is the model class for table "webhook".
 *
 * @property int $webhook_id
 * @property string $event
 * @property string $endpoint
 * @property string $method
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Staff $createdBy
 * @property Staff $updatedBy
 */
class Webhook extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'webhook';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['event', 'endpoint'], 'required'],
            [['method'], 'string'],
            [['created_by', 'updated_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['event'], 'string', 'max' => 50],
            [['endpoint'], 'string', 'max' => 255],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['created_by' => 'staff_id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['updated_by' => 'staff_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'webhook_id' => 'Webhook ID',
            'event' => 'Event',
            'endpoint' => 'Endpoint',
            'method' => 'Method',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * callback with current event data on event
     * @param $eventData
     * @return \yii\httpclient\Response
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function callWebhook($eventData) {

        $client = new Client();
        $response = $client->createRequest()
            ->setMethod($this->method)
            ->setUrl($this->endpoint)
            ->setFormat(Client::FORMAT_JSON)
            ->setData($eventData)
            ->addHeaders([
                'content-type' => 'application/json',
            ])
            ->send();

        return $response;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'updated_by']);
    }
}
