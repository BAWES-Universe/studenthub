<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "mall".
 *
 * @property string $mall_uuid
 * @property string $mall_name_en
 * @property string $mall_name_ar
 * @property string $mall_created_datetime
 * @property string $mall_updated_datetime
 *
 * @property Store[] $stores
 */
class Mall extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mall';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mall_name_en', 'mall_name_ar'], 'required'],
            [['mall_name_en'], 'unique'],
            [['mall_created_datetime', 'mall_updated_datetime'], 'safe'],
            [['mall_uuid'], 'string', 'max' => 60],
            [['mall_name_en', 'mall_name_ar'], 'string', 'max' => 255],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'mall_uuid',
                ],
                'value' => function() {
                    if (!$this->mall_uuid)
                        $this->mall_uuid = 'mall_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->mall_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'mall_created_datetime',
                'updatedAtAttribute' => 'mall_updated_datetime',
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
            'mall_uuid' => 'Mall Uuid',
            'mall_name_en' => 'Mall Name En',
            'mall_name_ar' => 'Mall Name Ar',
            'mall_created_datetime' => 'Mall Created Datetime',
            'mall_updated_datetime' => 'Mall Updated Datetime',
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'candidates',
            'stores',
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return array_merge(parent::fields(), [
            'candidate_count' => function($data) {
                return $this->getCandidates()->count();
            }
            ,'store_count' => function($data) {
                return $this->getStores()->count();
            }
        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\common\models\Store")
    {
        return $this->hasMany($modelClass::className(), ['mall_uuid' => 'mall_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['store_id' => 'store_id'])
            ->via('stores');
    }
}
