<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "{{%candidate_experience}}".
 *
 * @property string $candidate_experience_id
 * @property string $candidate_id
 * @property string $experience
 * @property string $employer
 * @property number $start_year
 * @property number $end_year
 * @property string $deleted
 * @property string $candidate_experience_created_at
 *
 * @property Candidate $candidate
 */
class CandidateExperience extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%candidate_experience}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_id', 'experience'], 'required'],
            [['candidate_experience_created_at'], 'safe'],
            [['experience', 'employer'], 'string', 'max' => 128],
            [['start_year', 'end_year'], 'number'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'candidate_experience_id' => Yii::t('app', 'Candidate Experience ID'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'employer' => Yii::t('app', 'Employer'),
            'start_year' => Yii::t('app', 'Start Year'),
            'end_year' => Yii::t('app', 'End Year'),
            'candidate_experience_created_at' => Yii::t('app', 'Candidate Experience Created At'),
        ];
    }
    
    /**
     * @return array
     */
    public function behaviors() 
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'candidate_experience_created_at',
                'updatedAtAttribute' => null,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave ($insert, $changedAttributes);

        //update profile status

        //$this->candidate->isInCompleteProfile();

        //$this->candidate->candidate_pending_profile = implode(',', array_keys($this->candidate->pendingProfile));
        $this->candidate->setScenario('updatePendingProfile');
        $this->candidate->save(false);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @inheritdoc
     * @return query\CandidateExperienceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateExperienceQuery(get_called_class());
    }
}
