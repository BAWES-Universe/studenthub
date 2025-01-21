<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "staff_notification".
 *
 * @property string $sn_uuid
 * @property int $staff_id
 * @property string $permission
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Staff $staff
 */
class StaffNotification extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'staff_notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['sn_uuid'], 'required'],
            [['staff_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['sn_uuid'], 'string', 'max' => 60],
            [['permission'], 'string', 'max' => 100],
            [['sn_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'sn_uuid',
                ],
                'value' => function() {
                    if(!$this->sn_uuid)
                        $this->sn_uuid = 'sn_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->sn_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
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
            'sn_uuid' => Yii::t('app', 'Sn Uuid'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'permission' => Yii::t('app', 'Permission'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }
}
