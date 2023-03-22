<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "staff_work_session".
 *
 * @property string $work_session_uuid
 * @property int $staff_id
 * @property int $total_minutes
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Staff $staff
 */
class StaffWorkSession extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'staff_work_session';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['work_session_uuid', 'created_at', 'updated_at'], 'required'],
            [['staff_id', 'total_minutes'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['work_session_uuid'], 'string', 'max' => 60],
            [['work_session_uuid'], 'unique'],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'work_session_uuid',
                ],
                'value' => function() {
                    if (!$this->work_session_uuid)
                        $this->work_session_uuid = 'work_session_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->work_session_uuid;
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

    public function extraFields()
    {
        return [
            'staff',
            'dayActivity'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'work_session_uuid' => Yii::t('app', 'Work Session Uuid'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'total_minutes' => Yii::t('app', 'Total Minutes'),
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

    public function getDayActivity($modelClass = "\common\models\StaffWorkSession") {
        return $this->hasMany($modelClass::className(), ['staff_id' => 'staff_id'])
            ->andWhere(['DATE(created_at)'=> new \yii\db\Expression("DATE('$this->created_at')")]);
    }
    /**
     * @inheritdoc
     * @return query\StaffWorkSessionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\StaffWorkSessionQuery(get_called_class());
    }
}
