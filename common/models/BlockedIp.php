<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "blocked_ip".
 *
 * @property string $ip_uuid
 * @property string|null $ip_address
 * @property string|null $note
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class BlockedIp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'blocked_ip';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
           // [['ip_uuid'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['ip_uuid'], 'string', 'max' => 60],
            [['ip_address'], 'string', 'max' => 45],
            [['note'], 'string', 'max' => 255],
            [['ip_uuid'], 'unique'],
        ];
    }

    /**
     *
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'ip_uuid',
                ],
                'value' => function () {
                    if (!$this->ip_uuid)
                        $this->ip_uuid = 'ip_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->ip_uuid;
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
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ip_uuid' => Yii::t('app', 'Ip Uuid'),
            'ip_address' => Yii::t('app', 'Ip Address'),
            'note' => Yii::t('app', 'Note'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }
}
