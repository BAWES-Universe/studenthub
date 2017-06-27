<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "university".
 *
 * @property integer $university_id
 * @property string $university_name_en
 * @property string $university_name_ar
 * @property integer $deleted
 *
 * @property Candidate[] $candidates
 */
class University extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'university';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['university_name_en', 'university_name_ar'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'university_id' => 'University ID',
            'university_name_en' => 'University Name En',
            'university_name_ar' => 'University Name Ar',
            'deleted' => 'Deleted'
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'candidates'
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        unset($fields['deleted']);
        $fields['total_candidates'] = function($model) {
            return sizeof($model->candidates);
        };

        return $fields;
    }    

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates()
    {
        return $this->hasMany(Candidate::className(),['university_id'=>'university_id']);
    }

    /**
     * soft delete university
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted = 1;
        return $this->save(false);
    }

    /**
     * @inheritdoc
     * @return query\UniversityQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\UniversityQuery(get_called_class());
    }
}
