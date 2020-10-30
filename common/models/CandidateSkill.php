<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "{{%candidate_skill}}".
 *
 * @property string $candidate_skill_id
 * @property string $candidate_id
 * @property string $skill
 * @property string $deleted
 * @property string $candidate_skill_created_at
 *
 * @property Candidate $candidate
 */
class CandidateSkill extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%candidate_skill}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_id', 'skill'], 'required'],
            [['candidate_skill_created_at'], 'safe'],
            [['skill'], 'string', 'max' => 128],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'candidate_skill_id' => Yii::t('app', 'Candidate Skill ID'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'skill' => Yii::t('app', 'Skill'),
            'candidate_skill_created_at' => Yii::t('app', 'Candidate Skill Created At'),
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
                'createdAtAttribute' => 'candidate_skill_created_at',
                'updatedAtAttribute' => null,
                'value' => new Expression('NOW()'),
            ],
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
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @inheritdoc
     * @return query\CandidateSkillQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateSkillQuery(get_called_class());
    }
}
