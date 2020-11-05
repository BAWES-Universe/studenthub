<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;


/**
 * This is the model class for table "candidate_note".
 *
 * @property string $candidate_note_uuid
 * @property integer $candidate_id
 * @property integer $staff_id
 * @property string $note_text
 * @property string $note_created_datetime
 * @property string $note_updated_datetime
 * 
 * @property Candidate[] $candidate
 * @property staff[] $Staff
 */
class CandidateNote extends \yii\db\ActiveRecord
{ 
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'candidate_note';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['candidate_id','note_text'], 'required'],
            [['note_created_datetime', 'note_updated_datetime','staff_id'], 'safe'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'candidate_note_uuid',
                ],
                'value' => function() {
                    if (!$this->candidate_note_uuid)
                        $this->candidate_note_uuid = 'can_nte_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->candidate_note_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'note_created_datetime',
                'updatedAtAttribute' => 'note_updated_datetime',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'staff_id',
                'updatedByAttribute' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'candidate_note_uuid' => Yii::t('candidate', 'ID'),
            'candidate_id' => Yii::t('candidate', 'Candidate ID'),
            'staff_id' => Yii::t('candidate', 'Staff ID'),
            'note_text' => Yii::t('candidate', 'Note'),
            'note_created_datetime' => Yii::t('candidate', 'Created At'),
            'note_updated_datetime' => Yii::t('candidate', 'Updated At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        $fields['note_text'] = function($model) {
            return html_entity_decode($model->note_text);
        };

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'candidate',
            'staff'
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
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }
}
