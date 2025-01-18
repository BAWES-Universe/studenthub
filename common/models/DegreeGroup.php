<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;

/**
 * This is the model class for table "degree_group".
 *
 * @property string $degree_group_uuid
 * @property string $degree_group_name_en
 * @property string $degree_group_name_ar
 * @property integer $degree_group_sort_order
 * @property integer $skip_major
 * @property string $degree_group_created_at
 * @property string $degree_group_updated_at
 *
 * @property Degree[] $degrees
 */
class DegreeGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'degree_group';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['degree_group_name_en'], 'required'],
            [['degree_group_created_at', 'degree_group_updated_at','skip_major'], 'safe'],
            [['degree_group_uuid'], 'string', 'max' => 60],
            [['degree_group_sort_order'], 'integer', 'max' => 3],
            [['degree_group_name_en', 'degree_group_name_ar'], 'string', 'max' => 255],
            [['degree_group_uuid'], 'unique'],
        ];
    }
    
    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'degree_group_uuid',
                ],
                'value' => function() {
                    if(!$this->degree_group_uuid)
                        $this->degree_group_uuid = 'degree_group_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                    
                    return $this->degree_group_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'degree_group_created_at',
                'updatedAtAttribute' => 'degree_group_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'degree_group_uuid' => Yii::t('app','Degree Group UUID'),
            'degree_group_name_en' => Yii::t('app','Degree Group Name - English'),
            'degree_group_name_ar' => Yii::t('app','Degree Group Name - Arabic'),
            'degree_group_sort_order' => Yii::t('app','Degree Group Sort Order'),
            'skip_major' => Yii::t('app','Skip Major Minor Selection'),
            'degree_group_created_at' => Yii::t('app','Degree Group Created At'),
            'degree_group_updated_at' => Yii::t('app','Degree Group Updated At'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function extraFields() {
        return [
            'degrees',
        ];
    }
    
    /**
     * Find by UUID 
     * @param number $id
     * @return Degree|array|null
     */
    public static function findByUUID($id)
    {
        return self::find()
            ->where(['degree_group_uuid' =>  $id])
            ->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDegrees($modelClass = '\common\models\Degree')
    {
        return $this->hasMany($modelClass::className(), ['degree_group_uuid' => 'degree_group_uuid']);
    }
}
