<?php

namespace common\models;

use Yii;
use common\models\Candidate;

/**
 * This is the model class for table "country".
 *
 * @property integer $country_id
 * @property string $country_name_en
 * @property string $country_name_ar
 * @property string $country_nationality_name_en
 * @property string $country_nationality_name_ar
 *
 * @property Candidate[] $candidates
 */
class Country extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'country';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['country_name_en', 'country_nationality_name_en'], 'required'],
            [['country_name_en', 'country_name_ar', 'country_nationality_name_en', 'country_nationality_name_ar'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'country_id' => Yii::t('app','Country ID'),
            'country_name_en' => Yii::t('app','Country Name En'),
            'country_name_ar' => Yii::t('app','Country Name Ar'),
            'country_nationality_name_en' => Yii::t('app','Nationality Name En'),
            'country_nationality_name_ar' => Yii::t('app','Nationality Name Ar'),
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
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates()
    {
        return $this->hasMany(Candidate::className(), ['country_id' => 'country_id']);
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();
        
        $fields['total_candidates'] = function($model) {
            return (int) sizeof($model->candidates);
        };
        return $fields;
    }

    /**
     * @inheritdoc
     * @return query\CountryQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CountryQuery(get_called_class());
    }
}
