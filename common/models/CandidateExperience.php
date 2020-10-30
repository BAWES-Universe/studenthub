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
            [['experience'], 'string', 'max' => 128],
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
            'experience' => Yii::t('app', 'Experience'),
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
