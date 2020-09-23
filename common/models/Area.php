<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;


/**
 * This is the model class for table "area".
 *
 * @property string $area_uuid
 * @property string $area_name_en
 * @property string $area_name_ar
 * @property string $area_created_at
 * @property string $area_updated_at
 * @property string $area_created_by
 * @property string $area_updated_by
 *
 * @property City $cityUu
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
            [['area_name_en', 'area_name_ar'], 'required'],
            [['area_created_at', 'area_updated_at'], 'safe'],
            [['area_uuid', 'area_created_by', 'area_updated_by'], 'string', 'max' => 60],
            [['area_name_en', 'area_name_ar'], 'string', 'max' => 255],
            [['area_uuid'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'area_uuid' => Yii::t('app', 'Area Uuid'),
            'area_name_en' => Yii::t('app', 'Area Name En'),
            'area_name_ar' => Yii::t('app', 'Area Name Ar'),
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
                'class' => BlameableBehavior::className(),
                'createdByAttribute' => 'area_created_by',
                'updatedByAttribute' => 'area_updated_by',
            ],
            [
                'class' => AttributeBehavior::className(),
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
        foreach ($response->results as $key => $address_component) {
            foreach($address_component->address_components as $component) {
                if(in_array('locality', $component->types)) {
                    return $component;
                }
            }
        }

        foreach ($response->results as $key => $address_component) {
            foreach($address_component->address_components as $component) {
                if(in_array('administrative_area_level_1', $component->types)) {
                    return $component;
                }
            }
        }

//        foreach($response->results[0]->address_components as $component) {
//            if(in_array('locality', $component->types)) {
//                return $component;
//            }
//        }
        
        //in case not able to find city, return political area 
        
//        foreach($response->results[0]->address_components as $component) {
//            if(in_array('administrative_area_level_1', $component->types)) {
//                return $component;
//            }
//        }
    }

    /**
     * Add city if not available by Google API response 
     * @param string $url
     * @param type $area_name
     * @return type
     */
    public static function addByGoogleAPIResponse($url, $area_name = null)
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

        $area_name = $a->long_name;

        $area = Area::find()
            ->andWhere([
                'OR',
                [
                    'area_name_en' => $area_name
                ],
                [
                    'area_name_ar' => $area_name
                ],
            ])
            ->one(); 

        if($area) {
            return [
                'operation' => 'success',
                'area' => $area,
            ];
        }

        $area = new Area; 
        $area->area_name_en = $area_name; 
        $area->area_name_ar = $area_name; 
        $area->save();
        
        //$latitude = $response->results[0]->geometry->location->lat;
        //$longitude = $response->results[0]->geometry->location->lng;
        
        return [
            'operation' => 'success',
            'area' => $area
        ];
    }
      
    /**
     * Check if it is area the place we getting by place_id from google 
     * @param type $area_name
     * @param type $response
     * @return \common\models\Area
     */
    static function _isGooglePlaceIsArea($area_name, $response) {
        
        if(
            $area_name && 
            !in_array('locality', $response->types) && 
            !in_array('administrative_area_level_1', $response->types)
        ) {
            $area = Area::find()
                ->andWhere([
                    'OR',
                    [
                        'area_name_en' => $area_name
                    ],
                    [
                        'area_name_ar' => $area_name
                    ],
                ])
                ->one(); 
            
            if(!$area) {
                $area = new Area; 
                $area->area_name_en = $area_name; 
                $area->area_name_ar = $area_name; 
                $area->save();
            }
            
            return $area;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\common\models\Candidate")
    {
        return $this->hasMany($modelClass::className(), ['area_uuid' => 'candidate_area_uuid']);
    }
}
