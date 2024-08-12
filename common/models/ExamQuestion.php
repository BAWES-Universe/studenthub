<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "exam_question".
 *
 * @property string $question_uuid
 * @property string $exam_uuid
 * @property int $question_type checkbox radio text file boolean number etc
 * @property string $question_en
 * @property string $question_ar
 * @property string $question_file_extensions
 * @property int $question_file_maxsize
 * @property int $staff_id
 * @property int $question_sort_order
 * @property int $is_deleted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Exam $examUu
 * @property Staff $staff
 * @property ExamQuestionAnswer[] $examQuestionAnswers
 * @property ExamQuestionChoice[] $examQuestionChoices
 */
class ExamQuestion extends \yii\db\ActiveRecord
{
    const TYPE_BOOLEAN = 1;
    const TYPE_MULTIPLE_CHOICE = 2;
    const TYPE_TEXT_INPUT = 3;
    const TYPE_FILE_INPUT = 4;
    const TYPE_INTEGER_INPUT = 5;
    const TYPE_DECIMAL_INPUT = 6;

    /**
     * @return array
     */
    public static function getTypeList() {
        return [
            self::TYPE_BOOLEAN => Yii::t('app', 'Boolean'),
            self::TYPE_MULTIPLE_CHOICE => Yii::t('app', 'Multiple Choice'),
            self::TYPE_TEXT_INPUT => Yii::t('app', 'Text Input'),
            self::TYPE_FILE_INPUT => Yii::t('app', 'File Input'),
            self::TYPE_INTEGER_INPUT => Yii::t('app', 'Integer Input'),
            self::TYPE_DECIMAL_INPUT => Yii::t('app', 'Decimal Input')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'exam_question';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['question_en'], 'required'],//'question_uuid',
            [['question_type', 'question_file_maxsize', 'staff_id', 'question_sort_order', 'is_deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['question_uuid', 'exam_uuid'], 'string', 'max' => 60],
            [['question_en', 'question_ar', 'question_file_extensions'], 'string', 'max' => 255],
            [['question_uuid'], 'unique'],
            [['question_file_extensions', 'question_file_maxsize'], 'required', 'when' => function($model) {
                return $this->question_type == Question::TYPE_FILE_INPUT;
            }],
            [['exam_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Exam::className(), 'targetAttribute' => ['exam_uuid' => 'exam_uuid']],
            [['staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Staff::className(), 'targetAttribute' => ['staff_id' => 'staff_id']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'question_uuid',
                ],
                'value' => function() {
                    if(!$this->question_uuid)
                        $this->question_uuid = 'question_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->question_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => "updated_at",
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields() {
        return [
            'examQuestionChoices',
            'examQuestionAnswers',
        ];
    }

    public function beforeDelete() {
        if (!parent::beforeDelete()) {
            return false;
        }

        //if ($this->question_type == self::TYPE_FILE_INPUT) {
        //    $this->_deleteFiles();
        //}

        ExamQuestionChoice::deleteAll(['question_uuid' => $this->question_uuid]);
        //ExamQuestionAnswer::deleteAll(['question_uuid' => $this->question_uuid]);

        return true;
    }

    /**
     * Delete files uploaded by candidate
     *
    private function _deleteFiles() {
        foreach ($this->questionAnswers as $answer) {
            Yii::$app->resourceManager->delete("candidate-answer/" . $answer->answer_value);
        }
    }*/

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'question_uuid' => Yii::t('app', 'Question Uuid'),
            'exam_uuid' => Yii::t('app', 'Exam Uuid'),
            'question_type' => Yii::t('app', 'Question Type'),
            'question_en' => Yii::t('app', 'Question En'),
            'question_ar' => Yii::t('app', 'Question Ar'),
            'question_file_extensions' => Yii::t('app', 'Question File Extensions'),
            'question_file_maxsize' => Yii::t('app', 'Question File Maxsize'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'question_sort_order' => Yii::t('app', 'Question Sort Order'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
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
    public function getStaff($modelClass = "\common\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExamQuestionAnswers($modelClass = "\common\models\ExamQuestionAnswer")
    {
        return $this->hasMany($modelClass::className(), ['question_uuid' => 'question_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExamQuestionChoices($modelClass = "\common\models\ExamQuestionChoice")
    {
        return $this->hasMany($modelClass::className(), ['question_uuid' => 'question_uuid']);
    }
}
