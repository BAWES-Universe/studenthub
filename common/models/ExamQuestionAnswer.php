<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "exam_question_answer".
 *
 * @property string $answer_uuid
 * @property string $exam_uuid
 * @property int $candidate_id
 * @property string $question_uuid
 * @property int $question_type
 * @property string $question_en
 * @property string $question_ar
 * @property string $answer
 * @property int $is_deleted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Exam $examUu
 * @property ExamQuestion $questionUu
 */
class ExamQuestionAnswer extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'exam_question_answer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id', 'question_en'], 'required'],//'answer_uuid',
            [['candidate_id', 'question_type', 'is_deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['answer_uuid', 'exam_uuid', 'question_uuid'], 'string', 'max' => 60],
            [['question_en', 'question_ar', 'answer'], 'string', 'max' => 255],
            [['answer_uuid'], 'unique'],
            [['answer'], '\common\components\S3FileExistValidator', 'filePath' => '',
                'message' => "Please upload file",
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'extensions' => $this->examQuestion->question_file_extensions,
                'maxSize' => $this->examQuestion->question_file_maxsize,
                'when' => function($model, $attribute) {
                    return $this->examQuestion && $model->{$attribute} !== $model->getOldAttribute($attribute) &&
                        $model->question->question_type == Question::TYPE_FILE_INPUT;
                }
            ],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['exam_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Exam::className(), 'targetAttribute' => ['exam_uuid' => 'exam_uuid']],
            [['question_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => ExamQuestion::className(), 'targetAttribute' => ['question_uuid' => 'question_uuid']],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'answer_uuid',
                ],
                'value' => function() {
                    if(!$this->answer_uuid)
                        $this->answer_uuid = 'answer_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->answer_uuid;
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
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'answer_uuid' => Yii::t('app', 'Answer Uuid'),
            'exam_uuid' => Yii::t('app', 'Exam Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'question_uuid' => Yii::t('app', 'Question Uuid'),
            'question_type' => Yii::t('app', 'Question Type'),
            'question_en' => Yii::t('app', 'Question En'),
            'question_ar' => Yii::t('app', 'Question Ar'),
            'answer' => Yii::t('app', 'Answer'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert) {

        parent::beforeSave($insert);

        if ($this->examQuestion->question_type == Question::TYPE_FILE_INPUT) {

            $fileName = $this->answer;

            $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
            
            $targetPath = "candidate-answer/" . $fileName;

            // Copy using S3ResourceManager Component

            try {

                Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

            } catch (\Aws\S3\Exception\S3Exception $e) {

                Yii::error($e->getMessage(), 'candidate');

                $this->addError('answer', Yii::t('app', 'Image not available to save.'));

                return false;

            } catch (Exception $e) {

                Yii::error($e->getMessage(), 'candidate');

                $this->addError('answer', Yii::t('app', 'Image not available to save.'));

                return false;
            }
        }

        return true;
    }

    /**
     * Add rule and tag for question of this answer
     * @param type $insert
     * @param type $changedAttributes
     */
    public function afterSave($insert, $changedAttributes) {

        // nothing to do if answer not got changed on update 

        if (!$insert && !isset($changedAttributes['answer']))
            return true;
  
        return true;
    }

    /**
     * @return string[]
     */
    public function extraFields() {
        return [
            'examQuestion',/* => function($model) {
                return $model->getExamQuestions()
                    ->with([
                        'examQuestionChoices'
                    ])
                    ->asArray()
                    ->one();
            },*/
            'examQuestionChoice',
            'candidate'
        ];
    }

    /**
     * @return bool
     */
    public function beforeDelete()
    {
        if(!parent::beforeDelete()) {
            return false;
        }

        //delete old file

        if ($this->examQuestion->question_type == Question::TYPE_FILE_INPUT && !empty($this->answer)) {
            Yii::$app->resourceManager->delete("candidate-answer/" . $this->answer);
        }

        return true;
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
    public function getExam($modelClass = "\common\models\Exam")
    {
        return $this->hasOne($modelClass::className(), ['exam_uuid' => 'exam_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestion($modelClass = "\common\models\ExamQuestion")
    {
        return $this->hasOne($modelClass::className(), ['question_uuid' => 'question_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExamQuestion($modelClass = "\common\models\ExamQuestion")
    {
        return $this->hasOne($modelClass::className(), ['question_uuid' => 'question_uuid']);
    }
}
