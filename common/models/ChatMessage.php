<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "chat_message".
 *
 * @property string $chat_message_uuid
 * @property string $chat_uuid
 * @property int $message_index
 * @property string $from
 * @property string $message
 * @property int $status 0-sent 1-received 2-read
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Chat $chat
 */
class ChatMessage extends \yii\db\ActiveRecord
{
    const STATUS_SENT = 0;
    const STATUS_RECEIVED = 1;
    const STATUS_READ = 2;

    const FROM_CANDIDATE = "candidate";
    const FROM_CONTACT = "contact";
    const FROM_STAFF = "staff";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat_message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['message_index', 'unique', 'targetAttribute' => 'chat_uuid'],
            [['chat_uuid', 'message'], 'required'],//'chat_message_uuid',
            [['from', 'message'], 'string'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['chat_message_uuid', 'chat_uuid'], 'string', 'max' => 60],
            [['chat_message_uuid'], 'unique'],
            [['chat_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Chat::className(), 'targetAttribute' => ['chat_uuid' => 'chat_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'chat_message_uuid' => Yii::t('app', 'Chat Message Uuid'),
            'chat_uuid' => Yii::t('app', 'Chat Uuid'),
            'from' => Yii::t('app', 'From'),
            'message' => Yii::t('app', 'Message'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'chat_message_uuid',
                ],
                'value' => function() {
                    if (!$this->chat_message_uuid)
                        $this->chat_message_uuid = 'chat_message_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    if(!$this->message_index)
                        $this->message_index = ChatMessage::find()->max('message_index') + 1;

                    return $this->chat_message_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChat($modelClass = "\common\models\Chat")
    {
        return $this->hasOne($modelClass::className(), ['chat_uuid' => 'chat_uuid']);
    }
}
