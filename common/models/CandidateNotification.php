<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_notification".
 *
 * @property string $cn_uuid
 * @property int $candidate_id
 * @property int $type
 * @property int $candidate_work_history_id
 * @property string $candidate_working_date_uuid
 * @property string $candidate_working_hour_uuid
 * @property string $invitation_uuid
 * @property string $request_uuid
 * @property number $tc_id
 * @property string $cwlf_uuid
 * @property int $company_id
 * @property int $store_id
 * @property int $staff_id
 * @property int $is_new
 * @property string $message
 * @property string $created_at
 * @property string $updated_at
 * @property string $appeal_uuid
 *
 * @property Candidate $candidate
 * @property CandidateWorkLogFeedback $candidateWorkLogFeedback
 * @property CandidateWorkHistory $candidateWorkHistory
 * @property CandidateWorkingDate $candidateWorkingDate
 * @property CandidateWorkingHour $candidateWorkingHour
 * @property Company $company
 * @property Invitation $invitation
 * @property Request $request
 * @property Staff $staff
 */
class CandidateNotification extends \yii\db\ActiveRecord
{
    const TYPE_INVITATION = 0;
    const TYPE_ASSIGNMENT = 1;
    const TYPE_UNASSIGNED = 2;
    const TYPE_WORK_APPROVED = 3;
    const TYPE_WORK_REJECTED = 4;
    const TYPE_TRANSFER_INIT = 5;
    const TYPE_TRANSFER_PAID = 6;
    const TYPE_TRANSFER_UNPAID = 7;
    const TYPE_WORK_SESSION_APPROVED = 8;
    const TYPE_WORK_SESSION_REJECTED = 9;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'type'], 'required'],//'cn_uuid',
            [['candidate_id', 'type', 'candidate_work_history_id', 'company_id', "store_id", "staff_id"], 'integer'],
            [['is_new'], "boolean"],
            [['is_new'], 'default', 'value'=> true],
            [['created_at', 'updated_at'], 'safe'],
            [['cn_uuid', 'candidate_working_date_uuid', 'invitation_uuid', 'request_uuid', "appeal_uuid"], 'string', 'max' => 60],
            [['cn_uuid'], 'unique'],
            [['message'], "string"],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
            [['cwlf_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkLogFeedback::className(), 'targetAttribute' => ['cwlf_uuid' => 'cwlf_uuid']],
            [['tc_id'], 'exist', 'skipOnError' => true, 'targetClass' => TransferCandidate::className(), 'targetAttribute' => ['tc_id' => 'tc_id']],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['candidate_work_history_id'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkHistory::className(), 'targetAttribute' => ['candidate_work_history_id' => 'id']],
            [['candidate_working_date_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkingDate::className(), 'targetAttribute' => ['candidate_working_date_uuid' => 'cwd_uuid']],
            [['candidate_working_hour_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkingHour::className(), 'targetAttribute' => ['candidate_working_hour_uuid' => 'candidate_working_hour_uuid']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
            [['invitation_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Invitation::className(), 'targetAttribute' => ['invitation_uuid' => 'invitation_uuid']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
            [['appeal_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkingHourAppeal::className(), 'targetAttribute' => ['appeal_uuid' => 'appeal_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'cn_uuid' => Yii::t('app', 'Cn Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'type' => Yii::t('app', 'Type'),
            'candidate_work_history_id' => Yii::t('app', 'Candidate Work History ID'),
            'candidate_working_date_uuid' => Yii::t('app', 'Candidate Working Date Uuid'),
            "candidate_working_hour_uuid" => Yii::t('app', 'Candidate Working Hour Uuid'),
            'invitation_uuid' => Yii::t('app', 'Invitation Uuid'),
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            "tc_id" => Yii::t('app', 'Candidate Transfer ID'),
            "cwlf_uuid"=> Yii::t('app', 'CWLF ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            "staff_id" => Yii::t('app', 'Staff ID'),
            'is_new' => Yii::t('app', 'Is New'),
            "message"=> Yii::t('app', 'Message'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'cn_uuid',
                ],
                'value' => function() {
                    if(!$this->cn_uuid)
                        $this->cn_uuid = 'cn_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->cn_uuid;
                }
            ],
        ];
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function fields()
    {
        $fields = parent::fields();

        $fields['is_new'] = function ($data) {
            return (boolean) $data->is_new;
        };

        return $fields;
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            "candidate",
            "candidateWorkHistory",
            "candidateWorkingDate",
            "candidateWorkingHour",
            "candidateWorkLogFeedback",
            "company",
            "store",
            "staff",
            "invitation",
            "request"
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkLogFeedback($modelClass = "\common\models\CandidateWorkLogFeedback")
    {
        return $this->hasOne($modelClass::className(), ['cwlf_uuid' => 'cwlf_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkHistory($modelClass = "\common\models\CandidateWorkHistory")
    {
        return $this->hasOne($modelClass::className(), ['id' => 'candidate_work_history_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingHour($modelClass = "\common\models\CandidateWorkingHour")
    {
        return $this->hasOne($modelClass::className(), ['candidate_working_hour_uuid' => 'candidate_working_hour_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingDate($modelClass = "\common\models\CandidateWorkingDate")
    {
        return $this->hasOne($modelClass::className(), ['cwd_uuid' => 'candidate_working_date_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);
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
    public function getInvitation($modelClass = "\common\models\Invitation")
    {
        return $this->hasOne($modelClass::className(), ['invitation_uuid' => 'invitation_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidate($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasOne($modelClass::className(), ['tc_id' => 'tc_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }
}
