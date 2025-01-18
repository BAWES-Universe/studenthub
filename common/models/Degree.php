<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;

/**
 * This is the model class for table "degree".
 *
 * @property string $degree_uuid
 * @property string $degree_name_en
 * @property string $degree_name_ar
 * @property string $degree_group_uuid
 * @property integer $degree_sort_order
 * @property string $degree_created_at
 * @property string $degree_updated_at
 *
 * @property DegreeGroup $degreeGroup
 * @property CandidateEducation[] $candidateEducations
 */
class Degree extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'degree';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['degree_name_en', 'degree_name_ar'], 'required'],
            [['degree_created_at', 'degree_updated_at','degree_group_uuid'], 'safe'],
            [['degree_sort_order'], 'integer', 'max' => 3],
            [['degree_name_en', 'degree_name_ar'], 'string', 'max' => 255],
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
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'degree_uuid',
                ],
                'value' => function() {
                    if(!$this->degree_uuid)
                        $this->degree_uuid = 'degree_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                    
                    return $this->degree_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'degree_created_at',
                'updatedAtAttribute' => 'degree_updated_at',
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
            'degree_uuid' => Yii::t('app','Degree UUID'),
            'degree_name_en' => Yii::t('app','Degree Name - English'),
            'degree_name_ar' => Yii::t('app','Degree Name - Arabic'),
            'degree_group_uuid' => Yii::t('app','Degree Group UUID'),
            'degree_sort_order' => Yii::t('app','Degree Sort Order'),
            'degree_created_at' => Yii::t('app','Degree Created At'),
            'degree_updated_at' => Yii::t('app','Degree Updated At'),
        ];
    }

    public function extraFields()
    {
        return array_merge(['degreeGroup', parent::extraFields()]);
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateEducations($modelClass = '\common\models\CandidateEducation')
    {
        return $this->hasMany($modelClass::className(), ['degree_uuid' => 'degree_uuid']);
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getDegreeGroup($modelClass = '\common\models\DegreeGroup')
    {
        return $this->hasOne($modelClass::className(), ['degree_group_uuid' => 'degree_group_uuid']);
    }
    
    /**
     * Find by UUID 
     * @param number $id
     * @return Degree|array|null
     */
    public static function findByUUID($id)
    {
        return self::find()
            ->where(['degree_uuid' =>  $id])
            ->one();
    }
    /*
    public static function find() {
        return new \common\models\query\DegreeQuery(get_called_class());
    }*/
}
