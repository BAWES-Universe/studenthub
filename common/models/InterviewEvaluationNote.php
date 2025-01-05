<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "interview_evaluation_note".
 *
 * @property string $ien_uuid
 * @property string $ienv_uuid
 * @property string $note
 */
class InterviewEvaluationNote extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'interview_evaluation_note';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ienv_uuid'], 'required'],
            [['note'], 'string'],
            [['ien_uuid', 'ienv_uuid'], 'string', 'max' => 60],
            [['ien_uuid'], 'unique'],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'ien_uuid',
                ],
                'value' => function() {
                    if (!$this->ien_uuid)
                        $this->ien_uuid = 'ien_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->ien_uuid;
                }
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ien_uuid' => Yii::t('app', 'Ien Uuid'),
            'ienv_uuid' => Yii::t('app', 'Ienv Uuid'),
            'note' => Yii::t('app', 'Note'),
        ];
    }
}
