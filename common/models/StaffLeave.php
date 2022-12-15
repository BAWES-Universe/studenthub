<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "staff_leave".
 *
 * @property string $staff_leave_uuid
 * @property int $staff_id
 * @property string $from_date
 * @property string $to_date
 * @property string $note
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Staff $staff
 */
class StaffLeave extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'staff_leave';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['staff_leave_uuid', 'created_at', 'updated_at'], 'required'],
            [['staff_id'], 'integer'],
            [['from_date', 'to_date', 'created_at', 'updated_at'], 'safe'],
            [['note'], 'string'],
            [['staff_leave_uuid'], 'string', 'max' => 60],
            [['staff_leave_uuid'], 'unique'],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'staff_leave_uuid',
                ],
                'value' => function() {
                    if (!$this->staff_leave_uuid)
                        $this->staff_leave_uuid = 'staff_leave_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->staff_leave_uuid;
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
            'staff_leave_uuid' => Yii::t('app', 'Staff Leave Uuid'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'from_date' => Yii::t('app', 'From Date'),
            'to_date' => Yii::t('app', 'To Date'),
            'note' => Yii::t('app', 'Note'),
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
