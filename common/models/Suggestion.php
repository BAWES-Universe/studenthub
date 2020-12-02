<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;


/**
 * This is the model class for table "suggestion".
 *
 * @property string $suggestion_uuid
 * @property string $request_uuid
 * @property string $fulltimer_uuid
 * @property int $candidate_id
 * @property string $note_uuid
 * @property int $suggestion_status 1-Suggested , 2- rejected, 3- accepted
 * @property string $suggestion_datetime
 *
 * @property Candidate $candidate
 * @property Fulltimer $fulltimer
 * @property Note $note
 * @property Request $request
 */
class Suggestion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suggestion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_uuid', 'note_uuid'], 'required'],
            [['candidate_id', 'suggestion_status'], 'integer'],
            [['suggestion_datetime'], 'safe'],
            [['candidate_id', 'fulltimer_uuid'], 'validateCandidate', 'skipOnEmpty' => false],
            [['suggestion_uuid', 'request_uuid', 'fulltimer_uuid', 'note_uuid'], 'string', 'max' => 60],
            [['suggestion_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['fulltimer_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Fulltimer::className(), 'targetAttribute' => ['fulltimer_uuid' => 'fulltimer_uuid']],
            [['note_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Note::className(), 'targetAttribute' => ['note_uuid' => 'note_uuid']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'request',
            'candidate',
            'fulltimer',
            'note'
        ];
    }

    /**
     * Need candidate or fulltimer
     */
    public function validateCandidate($attribute)
    {
        if(!$this->candidate_id && !$this->fulltimer_uuid)
        {
            $this->addError($attribute, Yii::t('app', 'Missing {value}', ['value' => $attribute]));
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'suggestion_uuid',
                ],
                'value' => function() {
                    if (!$this->suggestion_uuid)
                        $this->suggestion_uuid = 'suggestion_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->suggestion_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'suggestion_datetime',
                'updatedAtAttribute' => null,
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
            'suggestion_uuid' => Yii::t('app', 'Suggestion Uuid'),
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'fulltimer_uuid' => Yii::t('app', 'Fulltimer Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'note_uuid' => Yii::t('app', 'Note Uuid'),
            'suggestion_status' => Yii::t('app', 'Suggestion Status'),
            'suggestion_datetime' => Yii::t('app', 'Suggestion Datetime'),
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
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNote($modelClass = "\common\models\Note")
    {
        return $this->hasOne($modelClass::className(), ['note_uuid' => 'note_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }
}
