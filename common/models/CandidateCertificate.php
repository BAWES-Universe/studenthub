<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "candidate_certificate".
 *
 * @property string $certificate_uuid
 * @property int $certificate_type
 * @property int $candidate_id
 * @property int $candidate_work_history_id
 * @property string $exam_uuid
 * @property int $store_id
 * @property int $company_id
 * @property int $parent_company_id
 * @property string $start_date
 * @property string $end_date
 * @property int $staff_id
 * @property int $is_deleted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property CandidateWorkHistory $candidateWorkHistory
 * @property Company $company
 * @property Exam $examUu
 * @property Company $parentCompany
 * @property Staff $staff
 * @property Store $store
 */
class CandidateCertificate extends \yii\db\ActiveRecord
{
    const TYPE_EXPERIENCE = 0;
    const TYPE_EXAM = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'candidate_certificate';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', "certificate_type"], 'required'],//'certificate_uuid',
            [['certificate_type', 'candidate_id', 'candidate_work_history_id', 'store_id', 'company_id', 'parent_company_id', 'staff_id', 'is_deleted'], 'integer'],
            [['start_date', 'end_date', 'created_at', 'updated_at'], 'safe'],
            [['certificate_uuid', 'exam_uuid'], 'string', 'max' => 60],
            [['certificate_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['candidate_work_history_id'], 'exist', 'skipOnError' => true, 'targetClass' => CandidateWorkHistory::class, 'targetAttribute' => ['candidate_work_history_id' => 'id']],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
            [['exam_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Exam::class, 'targetAttribute' => ['exam_uuid' => 'exam_uuid']],
            [['parent_company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['parent_company_id' => 'company_id']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'staff_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::class, 'targetAttribute' => ['store_id' => 'store_id']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'certificate_uuid',
                ],
                'value' => function() {
                    if(!$this->certificate_uuid)
                        $this->certificate_uuid = 'certificate_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->certificate_uuid;
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
            'certificate_uuid' => Yii::t('app', 'Certificate Uuid'),
            'certificate_type' => Yii::t('app', 'Certificate Type'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'candidate_work_history_id' => Yii::t('app', 'Candidate Work History ID'),
            'exam_uuid' => Yii::t('app', 'Exam Uuid'),
            'store_id' => Yii::t('app', 'Store ID'),
            'company_id' => Yii::t('app', 'Company ID'),
            'parent_company_id' => Yii::t('app', 'Parent Company ID'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return string[]
     */
    public function extraFields()
    {
        return [
            'candidate',
            'store',
            'company',
            'parentCompany',
            "exam"
        ];
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
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExam($modelClass = "\common\models\Exam")
    {
        return $this->hasOne($modelClass::className(), ['exam_uuid' => 'exam_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'parent_company_id']);
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
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);
    }
}
