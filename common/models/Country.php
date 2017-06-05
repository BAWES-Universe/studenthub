<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "country".
 *
 * @property integer $country_id
 * @property string $country_name_en
 * @property string $country_name_ar
 * @property string $country_nationality_name_en
 * @property string $country_nationality_name_ar
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
            'country_id' => 'Country ID',
            'country_name_en' => 'Country Name En',
            'country_name_ar' => 'Country Name Ar',
            'country_nationality_name_en' => 'Country Nationality Name En',
            'country_nationality_name_ar' => 'Country Nationality Name Ar',
        ];
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
