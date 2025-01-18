<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "area".
 *
 * @property string $area_uuid
 * @property string $country_id
 * @property string $area_name_en
 * @property string $area_name_ar
 * @property string $area_latitude
 * @property string $area_longitude
 * @property string $area_created_at
 * @property string $area_updated_at
 * @property string $area_created_by
 * @property string $area_updated_by
 *
 * @property City $city
 */
class Area extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'area';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['area_name_en', 'area_name_ar', 'country_id'], 'required'],
            [['area_created_at', 'area_updated_at'], 'safe'],
            [['area_uuid', 'area_created_by', 'area_updated_by'], 'string', 'max' => 60],
            [['area_name_en', 'area_name_ar'], 'string', 'max' => 255],
            [['area_uuid'], 'unique'],
            [['country_id'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country_id' => 'country_id']]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'area_uuid' => Yii::t('app', 'Area Uuid'),
            'country_id' => Yii::t('app', 'Country Uuid'),
            'area_name_en' => Yii::t('app', 'Area Name En'),
            'area_name_ar' => Yii::t('app', 'Area Name Ar'),
            'area_latitude' => Yii::t('app', 'Area Latitude'),
            'area_longitude' => Yii::t('app', 'Area Longitude'),
            'area_created_at' => Yii::t('app', 'Area Created At'),
            'area_updated_at' => Yii::t('app', 'Area Updated At'),
            'area_created_by' => Yii::t('app', 'Area Created By'),
            'area_updated_by' => Yii::t('app', 'Area Updated By'),
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'area_created_by',
                'updatedByAttribute' => 'area_updated_by',
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'area_created_at',
                'updatedAtAttribute' => 'area_updated_at',
                'value' => new Expression('NOW()'),
            ],
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'area_uuid',
                ],
                'value' => function() {
                    if(!$this->area_uuid)
                        $this->area_uuid = 'area_'.Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();
                    
                    return $this->area_uuid;
                }
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function extraFields() {
        return [
            'candidates'
        ];
    }    

    /**
     * Get city object from Google API response 
     * @param type $response
     * @return type
     */
    public static function getGoogleAPICityObject($response) 
    {
        $result = isset($response->results) ? $response->results[0] : $response->result;

        foreach($result->address_components as $component) {
            if(in_array('locality', $component->types)) {
                return $component;
            }
        }
    
        foreach($result->address_components as $component) {
            if(in_array('administrative_area_level_1', $component->types)) {
                return $component;
            }
        }
    }

    /**
     * Get country object from Google API response 
     * @param type $response
     * @return type
     */
    public static function getGoogleAPICountryObject($response) 
    {
        $result = isset($response->results) ? $response->results[0] : $response->result;

        foreach($result->address_components as $component) {
            if(in_array('country', $component->types)) {
                return $component;
            }
        }
    }

    /**
     * Add city if not available by Google API response 
     * @param string $url
     * @param type $area_name
     * @return type
     */
    public static function addByGoogleAPIResponse($url, $selected_area_name = null, $selected_country_name = null)
    {
        $url .= '&key=' . Yii::$app->params['google_api_key'];
        $url .= '&location_type=APPROXIMATE';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, str_replace(' ', '+', $url));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = json_decode(curl_exec($ch));
        
        if(isset($response->result))
            $response->results = [$response->result];
        
        if(!$response || empty($response->results))
        {
            return [
                'operation' => 'error',
                'message' => Yii::t('app', 'Sorry not able to find your area!')
            ];
        }

        $a = self::getGoogleAPICityObject($response);

        if(!$a) {
            return [
                'operation' => 'error',
                'message' => Yii::t('app', 'Sorry not able to find your area!') 
            ];
        }

        $area_name = $selected_area_name? $selected_area_name : $a->long_name;

        $area = self::isExists($area_name, $selected_country_name); 

        if($area && $area->country) {
            return [
                'operation' => 'success',
                'area' => $area,
                'country' => $area->country
            ];
        }

        $objCountry = self::getGoogleAPICountryObject($response);

        if(empty($objCountry->long_name) || empty($response->results[0]->geometry->location->lat)) {
            return [
                'operation' => 'error',
                'message' => Yii::t('app', 'Sorry not able to find your city!')
            ];
        }

        $country_name = $objCountry->long_name;
        $country_code = $objCountry->short_name;
        $latitude = $response->results[0]->geometry->location->lat;
        $longitude = $response->results[0]->geometry->location->lng;
        
        //add country if not available 

        $country = Country::find()
            ->andWhere(['country_name_en' => $country_name])
            ->one();

        if(!$country)
        {
            //$countryInfo = json_decode(file_get_contents('https://restcountries.eu/rest/v2/alpha/' . $country_code));

            $country = new Country;
            $country->country_name_en = $country_name;
            $country->country_name_ar = $country_name;
            $country->country_nationality_name_en = $country_name;//$countryInfo->demonym;
            $country->country_nationality_name_ar = $country_name;//$countryInfo->demonym;
            $country->country_from_google_map = 1;

            if(!$country->save()) {
                
                return [
                    'operation' => 'error',
                    'message' => $country->getErrors()
                ];
            }
        }

        $area = new Area; 
        $area->country_id = $country->country_id;
        $area->area_name_en = $area_name; 
        $area->area_name_ar = $area_name; 
        $area->area_latitude = $latitude;
        $area->area_longitude = $longitude;

        if(!$area->save()) {
            return [
                'operation' => 'error',
                'message' => $area->getErrors()
            ];
        }
        
        return [
            'operation' => 'success',
            'area' => $area,
            'country' => $country
        ];
    }
    
    /**
     * Check if already in DB 
     */
    public static function isExists($area_name, $country_name) {

        $query = Area::find()   
            ->joinWith('country')    
            ->andWhere([
                'OR',
                [
                    'area_name_en' => $area_name
                ],
                [
                    'area_name_ar' => $area_name
                ]
            ]);
        
        
        //can have same area/city name in different country 
        
        if($country_name)  
        {
            $query->andWhere([
                'OR',
                [
                    'country_name_en' => $country_name
                ],
                [
                    'country_name_ar' => $country_name
                ],
            ]);
        }
        
        return $query->one();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['candidate_area_uuid' => 'area_uuid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\common\models\Country")
    {
        return $this->hasOne($modelClass::className(), ['country_id' => 'country_id']);
    }
}
