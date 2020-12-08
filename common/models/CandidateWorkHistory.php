<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%candidate_work_history}}".
 *
 * @property integer $id
 * @property integer $candidate_id
 * @property integer $store_id
 * @property integer $company_id
 * @property integer $parent_company_id
 * @property string $start_date
 * @property string $end_date
 * @property string $candidate_hourly_rate
 */
class CandidateWorkHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%candidate_work_history}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_id', 'store_id','parent_company_id','company_id'], 'integer'],
            [['start_date', 'end_date'], 'safe'],
            [['candidate_hourly_rate'], 'number'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            'parent_company_id' => Yii::t('app', 'parent company ID'),
            'company_id' => Yii::t('app', 'company ID'),
            'start_date' => Yii::t('app', 'Start Date'),
            'end_date' => Yii::t('app', 'End Date'),
            'candidate_hourly_rate' => Yii::t('app', 'Candidate Hourly Rate'),
        ];
    }

    /**
     * save candidate assigned history
     * @param $candidate
     * @return bool
     */
    public static function saveAssignedHistory($candidate) {
        $model = new CandidateWorkHistory();
        $model->candidate_id = $candidate->candidate_id;
        $model->store_id = $candidate->store_id;
        $model->company_id = (isset($candidate->company->company_id)) ? $candidate->company->company_id : null;
        $model->parent_company_id = (isset($candidate->company->parent_company_id)) ? $candidate->company->parent_company_id : $candidate->company->company_id;
        $model->start_date  = new \yii\db\Expression('NOW()');
        $model->candidate_hourly_rate = $candidate->candidate_hourly_rate;
        if ($model->save()) {
            return true;
        } else {
            return $model->errors;
        }
    }

    /**
     * save candidate un-assign record
     * @param $candidate
     * @return array
     */
    public static function saveUnAssignedHistory($candidate) {

        // check if candidate assigned today then delete assigned history
        if (CandidateWorkHistory::checkTotalHistory($candidate)) {
            return CandidateWorkHistory::deleteAll(['candidate_id'=>$candidate->candidate_id,'start_date'=>date('Y-m-d')]);
        } else {
            // else save unassigned history
            $model = CandidateWorkHistory::find()
                ->filterCandidate($candidate->candidate_id)
                ->emptyEndDate()
                ->one();
            if ($model) {
                $model->end_date  = new \yii\db\Expression('NOW()');
                if ($model->save()) {
                    return [
                        'operation' =>'success',
                        'message' =>Yii::t('candidate','record successfully updated')
                    ];
                } else {
                    return [
                        'operation' =>'error',
                        'message' =>Yii::t('candidate','error while updating record. Please try again')
                    ];
                }
            } else {
                return [
                    'operation' =>'error',
                    'message' =>Yii::t('app','no record found')
                ];
            }
        }
    }

    /**
     * check is candidate assigned
     * today to store
     * @param $candidate
     * @return mixed
     */
    public static function checkTotalHistory($candidate) {
        return CandidateWorkHistory::find()
        ->filterCandidate($candidate->candidate_id)
        ->filterDate(date('Y-m-d'))
        ->exists();
    }


    public function extraFields()
    {
        return [
            'store',
            'company'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($className = '\common\models\Company') {
        return $this->hasOne($className::className(), ['company_id' => 'company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($className = '\common\models\Store') {
        return $this->hasOne($className::className(), ['store_id' => 'store_id']);
    }

    /**
     * @inheritdoc
     * @return CandidateWorkHistoryQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateWorkHistoryQuery(get_called_class());
    }
}
