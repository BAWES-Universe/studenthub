<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "cron_log".
 *
 * @property int $id
 * @property string $task
 * @property string|null $last_ran_at
 * @property string|null $last_output
 */
class CronLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cron_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['task'], 'required'],
            [['last_ran_at'], 'safe'],
            [['last_output'], 'string'],
            [['task'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'task' => Yii::t('app', 'Task'),
            'last_ran_at' => Yii::t('app', 'Last Ran At'),
            'last_output' => Yii::t('app', 'Last Output'),
        ];
    }
}
