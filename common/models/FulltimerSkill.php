<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "{{%fulltimer_skill}}".
 *
 * @property string $fulltimer_skill_id
 * @property string $fulltimer_uuid
 * @property string $skill
 * @property string $deleted
 * @property string $fulltimer_skill_created_at
 *
 * @property Fulltimer $fulltimer
 */
class FulltimerSkill extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%fulltimer_skill}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fulltimer_uuid', 'skill'], 'required'],
            [['fulltimer_skill_created_at'], 'safe'],
            [['skill'], 'string', 'max' => 128],
            [['fulltimer_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => Fulltimer::className(), 'targetAttribute' => ['fulltimer_uuid' => 'fulltimer_uuid']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fulltimer_skill_id' => Yii::t('app', 'Fulltimer Skill ID'),
            'fulltimer_uuid' => Yii::t('app', 'Fulltimer ID'),
            'skill' => Yii::t('app', 'Skill'),
            'fulltimer_skill_created_at' => Yii::t('app', 'Fulltimer Skill Created At'),
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'fulltimer_skill_created_at',
                'updatedAtAttribute' => null,
                'value' => new Expression('NOW()'),
            ],
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
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return $this->hasOne($modelClass::className(), ['fulltimer_uuid' => 'fulltimer_uuid']);
    }

    /**
     * @inheritdoc
     * @return query\FulltimerSkillQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\FulltimerSkillQuery(get_called_class());
    }
}
