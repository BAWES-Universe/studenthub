<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "{{%fulltimer_experience}}".
 *
 * @property string $fulltimer_experience_id
 * @property string $fulltimer_uuid
 * @property string $experience
 * @property string $deleted
 * @property string $fulltimer_experience_created_at
 *
 * @property Fulltimer $fulltimer
 */
class FulltimerExperience extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%fulltimer_experience}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fulltimer_uuid', 'experience'], 'required'],
            [['fulltimer_experience_created_at'], 'safe'],
            [['experience'], 'string', 'max' => 128],
            [['fulltimer_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Fulltimer::class, 'targetAttribute' => ['fulltimer_uuid' => 'fulltimer_uuid']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        unset($fields['deleted']);

        return $fields;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fulltimer_experience_id' => Yii::t('app', 'Fulltimer Experience ID'),
            'fulltimer_uuid' => Yii::t('app', 'Fulltimer ID'),
            'experience' => Yii::t('app', 'Experience'),
            'fulltimer_experience_created_at' => Yii::t('app', 'Fulltimer Experience Created At'),
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'fulltimer_experience_created_at',
                'updatedAtAttribute' => null,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @inheritdoc
     * @return query\FulltimerExperienceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\FulltimerExperienceQuery(get_called_class());
    }
}
