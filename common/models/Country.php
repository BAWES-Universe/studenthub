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
 * @property integer $country_from_google_map
 * @property string|null $currency_code
 * @property Currency $currency
 * @property Candidate[] $candidates
 */
class Country extends \yii\db\ActiveRecord
{
    const FROM_GOOGLE_MAP = 1;
    const NOT_FROM_GOOGLE_MAP = 0;

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
            ['country_from_google_map', 'in', 'range' => [self::NOT_FROM_GOOGLE_MAP, self::FROM_GOOGLE_MAP]],
            [['country_name_en'], 'unique'],
            [['currency_code'], 'string', "max" => 3],
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
            'country_from_google_map' => Yii::t('app', 'Added by Google API'),
            "currency_code" => Yii::t('app','Currency Code'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'candidates',
            "totalCandidates"
        ];
    }

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCurrency($modelClass = "\common\models\Currency")
    {
        return $this->hasOne($modelClass::className(), ['code' => 'currency_code']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['country_id' => 'country_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAreas($modelClass = "\common\models\Area")
    {
        return $this->hasMany($modelClass::className(), ['country_id' => 'country_id']);
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        /*$fields['total_candidates'] = function($model) {
            return (int) sizeof($model->candidates);
        };*/

        return $fields;
    }

    public function getTotalCandidates() {
        return $this->getCandidates()->count();
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
