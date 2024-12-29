<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "job_skills".
 *
 * @property string $job_uuid
 * @property string $skill
 * @property string $skill_ar
 * @property Job $job
 */
class JobSkills extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'job_skills';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['job_uuid', 'skill', 'skill_ar'], 'required'],
            [['job_uuid'], 'string', 'max' => 60],
            [['skill'], 'string', 'max' => 255],
            [['job_uuid', 'skill'], 'unique', 'targetAttribute' => ['job_uuid', 'skill']],
            [['job_uuid'], 'exist', 'skipOnError' => true,
                'targetClass' => Job::className(), 'targetAttribute' => ['job_uuid' => 'job_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'job_uuid' => Yii::t('app', 'Job Uuid'),
            'skill' => Yii::t('app', 'Skill'),
            'skill_ar' => Yii::t('app', 'Skill - Arabic'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getJob($modelClass = "\common\models\Job")
    {
        return $this->hasOne($modelClass::className(), ['job_uuid' => 'job_uuid']);
    }
}
