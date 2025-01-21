<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\behaviors\BlameableBehavior;


/**
 * This is the model class for table "{{%candidate_tag}}".
 *
 * @property string $candidate_tag_id
 * @property string $candidate_id
 * @property string $tag
 * @property string $reason
 * @property string $deleted
 * @property string $created_at
 * @property string $created_by
 *
 * @property Candidate $candidate
 */
class CandidateTag extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%candidate_tag}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_id', 'tag'], 'required'],
            [['created_at'], 'safe'],
            [['tag'], 'string', 'max' => 128],
            [['reason'], 'string'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::class, 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::class, 'targetAttribute' => ['staff_id' => 'created_by']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'candidate_tag_id' => Yii::t('app', 'Candidate Tag ID'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'tag' => Yii::t('app', 'Tag'),
            'reason' => Yii::t('app', 'Reason'),
            'created_at' => Yii::t('app', 'Candidate Tag Created At'),
            'created_by' => Yii::t('app', 'Candidate Tag Created By'),
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null,
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => false,
            ],
        ];
    }

    public function extraFields()
    {
        return array_merge(['createdBy'], parent::extraFields());
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
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave ($insert, $changedAttributes);

        //update profile status

        //$this->candidate->isInCompleteProfile();

        //$this->candidate->candidate_pending_profile = implode(',', array_keys($this->candidate->pendingProfile));
        //$this->candidate->setScenario('updatePendingProfile');
        //$this->candidate->save(false);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'created_by']);
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
     * @return query\CandidateTagQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CandidateTagQuery(get_called_class());
    }
}
