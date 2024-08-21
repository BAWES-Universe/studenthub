<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "exam".
 *
 * @property string $exam_uuid
 * @property string $title_en
 * @property string $title_ar
 * @property string $description_en
 * @property string $description_ar
 * @property int $staff_id
 * @property int $is_deleted
 * @property string $created_at
 * @property string $updated_at
 *
 * @property CandidateCertificate[] $candidateCertificates
 * @property ExamQuestion[] $examQuestions
 * @property ExamQuestionAnswer[] $examQuestionAnswers
 */
class Exam extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'exam';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title_en'], 'required'],//'exam_uuid',
            [['staff_id', 'is_deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['exam_uuid'], 'string', 'max' => 60],
            [['title_en', 'title_ar', 'description_en', 'description_ar'], 'string', 'max' => 255],
            [['exam_uuid'], 'unique'],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'exam_uuid',
                ],
                'value' => function() {
                    if(!$this->exam_uuid)
                        $this->exam_uuid = 'exam_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->exam_uuid;
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
            'exam_uuid' => Yii::t('app', 'Exam Uuid'),
            'title_en' => Yii::t('app', 'Title En'),
            'title_ar' => Yii::t('app', 'Title Ar'),
            'description_en' => Yii::t('app', 'Description En'),
            'description_ar' => Yii::t('app', 'Description Ar'),
            'staff_id' => Yii::t('app', 'Staff ID'),
            'is_deleted' => Yii::t('app', 'Is Deleted'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateCertificates($modelClass = "\common\models\CandidateCertificate")
    {
        return $this->hasMany($modelClass::className(), ['exam_uuid' => 'exam_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExamQuestions($modelClass = "\common\models\ExamQuestion")
    {
        return $this->hasMany($modelClass::className(), ['exam_uuid' => 'exam_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExamQuestionAnswers($modelClass = "\common\models\ExamQuestionAnswer")
    {
        return $this->hasMany($modelClass::className(), ['exam_uuid' => 'exam_uuid']);
    }
}
