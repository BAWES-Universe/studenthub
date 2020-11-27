<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "fulltimer_tags".
 *
 * @property int $fulltimer_tags_id
 * @property string $fulltimer_uuid
 * @property string $tag
 *
 * @property Fulltimer $fulltimerUu
 */
class FulltimerTags extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fulltimer_tags';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fulltimer_uuid'], 'required'],
            [['fulltimer_uuid'], 'string', 'max' => 60],
            [['tag'], 'string', 'max' => 255],
            [['fulltimer_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Fulltimer::className(), 'targetAttribute' => ['fulltimer_uuid' => 'fulltimer_uuid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'fulltimer_tags_id' => Yii::t('app', 'Fulltimer Tags ID'),
            'fulltimer_uuid' => Yii::t('app', 'Fulltimer Uuid'),
            'tag' => Yii::t('app', 'Tag'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }
}
