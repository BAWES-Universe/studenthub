<?php

namespace common\components;

use yii\httpclient\Client;
use common\models\Area;


class GoogleMap {
    
    public $accessKey; 
    
    private $endPoint = 'https://maps.googleapis.com/maps/api/';
    
    private $client; 
    
    public function __construct() {
        $this->client = new Client(['baseUrl' => $this->endPoint]);
    }

    /**
     * return list of places by keyword 
     * @return type
     */
    public function getPlacePredictions($query) {

        $response = $this->client->createRequest()
            ->setMethod('GET')
            ->setUrl('place/autocomplete/json')
            ->setData([
                'types' => '(regions)',//(cities)
                'input' => $query,
                'componentRestrictions' => [ 'country' => ['kw'] ],
                'country' => 'kw',

                'key' => $this->accessKey])
            ->send();
        
        return $response->getData()['predictions'];
    }
    
    /**
     * Return place detail by google map place_id
     * @param string $place_id
     * @param string $name
     * @param string $country_name
     * @return type
     */
    public function placeDetail($place_id, $name = null, $country_name = null) {
        
        if($name) {
            $model = $this->_isExists($name);
            
            if($model)
            {
                return [
                    'operation' => 'success',
                    'area' => $model,
                    'country' => $model->country
                ];
            }
        }

        $url = $this->endPoint . 'place/details/json?placeid=' . $place_id;
        
        return Area::addByGoogleAPIResponse($url, $name);
    }

    /**
     * Check if already in DB 
     */
    private function _isExists($area_name) {

        return Area::find()   
            ->andWhere([
                'OR',
                [
                    'area_name_en' => $area_name
                ],
                [
                    'area_name_ar' => $area_name
                ]
            ])
            ->one();
    }
}