<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;

/**
 * This is the model class for table "request".
 *
 * @property string $request_uuid
 * @property int $company_id Which company is this request for?
 * @property string $contact_uuid Which contact from this company made the request?
 * @property int $request_created_by
 * @property int $request_updated_by
 * @property int $request_position_type 1 - Fulltime, 2 - Partime
 * @property string $request_position_title the job title being requested
 * @property string $request_job_description
 * @property string $request_compensation
 * @property int $request_number_of_employees
 * @property string $request_location
 * @property string $request_additional_info
 * @property string $request_status
 * @property string $request_feedback
 * @property string $request_created_datetime
 * @property string $request_updated_datetime
 *
 * @property Company $company
 * @property CompanyContact $contactUu
 * @property Staff $requestCreatedBy
 * @property Staff $requestUpdatedBy
 */
class Request extends \yii\db\ActiveRecord
{
    const STATUS_STARTED = 'started';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company_id','request_job_description','request_compensation'], 'required'],
            [['company_id', 'request_position_type', 'request_number_of_employees'], 'integer'],
            ['request_status', 'in', 'range' => [self::STATUS_STARTED, self::STATUS_DELIVERED, self::STATUS_CANCELLED]],
            [['request_created_datetime', 'request_updated_datetime'], 'safe'],
            [['request_additional_info','request_job_description','request_compensation', 'request_location'], 'string'],
            [['request_position_title', 'request_feedback'], 'string', 'max' => 255],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['contact_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CompanyContact::className(), 'targetAttribute' => ['contact_uuid' => 'contact_uuid']],
            //['contact_uuid', 'validateContact'] contact can be removed from company
        ];
    }

    public function validateContact($attribute, $params, $validator) {

        if ($this->contact_uuid && $this->company_id) {

            $exist = CompanyContact::find()->andWhere([
                'contact_uuid' => $this->contact_uuid,
                'company_id' => $this->company_id
            ])->exists();

            if (!$exist) {
                $this->addError('contact_uuid', "Contact Detail not belongs to this company.");
            }
        }
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'request_uuid',
                ],
                'value' => function() {
                    if (!$this->request_uuid)
                        $this->request_uuid = 'request_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->request_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'request_created_datetime',
                'updatedAtAttribute' => 'request_updated_datetime',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'request_created_by',
                'updatedByAttribute' => 'request_updated_by',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'company_id' => Yii::t('app', 'Which company is this request for?'),
            'contact_uuid' => Yii::t('app', 'Which contact from this company made the request?'),
            'request_created_by' => Yii::t('app', 'Request Created By'),
            'request_updated_by' => Yii::t('app', 'Request Updated By'),
            'request_position_type' => Yii::t('app', '1 - Fulltime, 2 - Partime'),
            'request_position_title' => Yii::t('app', 'the job title being requested'),
            'request_job_description' => Yii::t('app', 'Job Description'),
            'request_compensation' => Yii::t('app', 'Compensation'),
            'request_number_of_employees' => Yii::t('app', 'Request Number Of Employees'),
            'request_location' => Yii::t('app', 'Request Location'),
            'request_additional_info' => Yii::t('app', 'Request Additional Info'),
            'request_status' => Yii::t('app', 'Request Status'),
            'request_feedback' => Yii::t('app', 'Request Feedback'),
            'request_created_datetime' => Yii::t('app', 'Request Created Datetime'),
            'request_updated_datetime' => Yii::t('app', 'Request Updated Datetime'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'requestCreatedBy',
            'requestUpdatedBy',
            'requestCreatedByContact',
            'requestUpdatedByContact',
            'contact',
            'company',
            'lastActivity',
            'requestActivities',
            'suggestions',
            'activeSuggestions',
            'invitations'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'contact_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'request_created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'request_updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedByContact($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'request_created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedByContact($modelClass = "\common\models\Contact")
    {
        return $this->hasOne($modelClass::className(), ['contact_uuid' => 'request_updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastActivity($modelClass = "\common\models\Note")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('note_created_datetime DESC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestActivities($modelClass = "\common\models\Note")
    {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('note_created_datetime DESC');
    }

    public function getSuggestions($modelClass = "\common\models\Suggestion") {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('suggestion_datetime DESC');
    }

    public function getInvitations($modelClass = "\common\models\Invitation") {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->orderBy('invitation_created_at DESC');
    }

    public function getActiveSuggestions($modelClass = "\common\models\Suggestion") {
        return $this->hasMany($modelClass::className(), ['request_uuid' => 'request_uuid'])
            ->andWhere(['suggestion_status'=>Suggestion::TYPE_SUGGESTED]);
    }

    /**
     * create activity record for request
     * @param type $detail
     * @return type
     */
    public function createRequestActivity($detail = null)
    {
        $model = new Note();
        $model->request_uuid = $this->request_uuid;
        $model->contact_uuid = $this->contact_uuid;
        $model->company_id = $this->company_id;
        $model->note_text = $detail;
        $model->save(false);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub

        Company::updateRequest($this->company_id);
    }

    public static function activeRequestCount() {
        return Request::find()
            ->andWhere(['request_status' => Request::STATUS_STARTED])
            ->andWhere(new \yii\db\Expression("request_updated_datetime < DATE_SUB(NOW(),INTERVAL 24 HOUR)"))//last 1 hour
            ->count();
    }

    public static function totalRequestCount() {
        return Request::find()
            ->andWhere(['in','request_status',[Request::STATUS_STARTED]])
            ->count();
    }

    /**
     * @inheritdoc
     * @return query\RequestQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\RequestQuery(get_called_class());
    }
}
