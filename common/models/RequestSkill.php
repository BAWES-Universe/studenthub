<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "request_skill".
 *
 * @property string $request_uuid
 * @property string $skill
 *
 * @property Request $request
 */
class RequestSkill extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'request_skill';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['request_uuid', 'skill'], 'required'],
            [['request_uuid'], 'string', 'max' => 60],
            [['skill'], 'string', 'max' => 128],
            [['request_uuid', 'skill'], 'unique', 'targetAttribute' => ['request_uuid', 'skill']],
            [['request_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Request::className(), 'targetAttribute' => ['request_uuid' => 'request_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'request_uuid' => Yii::t('app', 'Request Uuid'),
            'skill' => Yii::t('app', 'Skill'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }
}
