<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "request_activity".
 *
 * @property string $activity_uuid
 * @property string $request_uuid
 * @property string|null $staff_id
 * @property string $activity_detail
 * @property string $activity_created_datetime
 * @property string $activity_updated_datetime
 *
 * @property Request $request
 * @property Staff $staff
 */
class RequestActivity extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request_activity';
    }


    public function extraFields()
    {
        return [
            'staff'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_uuid', 'activity_detail'], 'required'],
            [['activity_detail'], 'string'],
            ['request_uuid', 'validateRequest'],
            [['activity_created_datetime', 'activity_updated_datetime', 'staff_id'], 'safe'],
            [['activity_uuid', 'request_uuid'], 'string', 'max' => 60],
            [['activity_uuid'], 'unique'],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }
    
    /**
     * can't update for cancelled/completed request
     * @param type $attribute
     * @param type $params
     * @param type $validator
     */
    public function validateRequest($attribute, $params, $validator)
    {   
        if(in_array($this->request->request_status, [Request::STATUS_CANCELLED, Request::STATUS_DELIVERED])) {
            $this->addError($attribute, Yii::t('app', "Can't update for cancelled/completed request."));
        } 
    }
    
    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'activity_uuid',
                ],
                'value' => function() {
                    if (!$this->activity_uuid)
                        $this->activity_uuid = 'act_req_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->activity_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'activity_created_datetime',
                'updatedAtAttribute' => 'activity_updated_datetime',
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
            'activity_uuid' => Yii::t('app', 'Activity Uuid'),
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'staff_id' => Yii::t('app', 'Staff id'),
            'activity_detail' => Yii::t('app', 'Activity Detail'),
            'activity_created_datetime' => Yii::t('app', 'Activity Created Datetime'),
            'activity_updated_datetime' => Yii::t('app', 'Activity Updated Datetime')
        ];
    }

    /**
     * Gets query for [[Request]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelName = '\common\models\Request')
    {
        return $this->hasOne($modelName::className(), ['request_uuid' => 'request_uuid']);
    }

    /**
     * Gets query for [[Staff]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelName = '\common\models\Staff')
    {
        return $this->hasOne($modelName::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @param bool $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        parent::beforeSave($insert);
             
        $message = Yii::t('staff', '[Update on request from {name} @ {email} by {staffName}] {activityDetail}', [
            'name' => $this->request->company->company_name,
            'email' => $this->request->company->company_email,
            'staffName' => $this->staff->staff_name,
            'activityDetail' => $this->activity_detail
        ]);
        
        Yii::info($message, __METHOD__);
        
        return true;
    }
    
    public function afterSave($insert, $changedAttributes) {
        parent::afterSave($insert, $changedAttributes);
        
        if($insert && $this->request) {

//            update `request_updated_at` field
            $this->request->request_updated_datetime = '';
            $this->request->update(false);
            Company::updateRequest($this->request->company_id);
        }
    }
}
