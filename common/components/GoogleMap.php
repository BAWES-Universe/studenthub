<?php

namespace common\components;

use yii\httpclient\Client;
use common\models\Area;


class GoogleMap extends \yii\base\Component {
    
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
    public function getPlacePredictions($query, $country_name) {

        $iosCode = [
            'Kuwait' => 'kw',
            'Bahrain' => 'BH',
            'UAE' => 'AE',
            'United Arab Emirates' => 'AE',
            'KSA' => 'SA',
            'Saudi Arabia' => 'SA',
            'Qatar' => 'QA'
        ];
        
        $data = [
            'types' => '(regions)',//(cities)
            'input' => $query,
            'key' => $this->accessKey
        ];

        if(isset($iosCode[$country_name])) {
            $data = array_merge($data, [
                'components' => 'country:' . $iosCode[$country_name],
                'componentRestrictions' => [ 'country' => [$iosCode[$country_name]] ],
                'country' => $iosCode[$country_name],
            ]);
        }
        
        $response = $this->client->createRequest()
            ->setMethod('GET')
            ->setUrl('place/autocomplete/json')
            ->setData($data)
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
            $model = Area::isExists($name, $country_name);
            
            if($model && $model->country)
            {
                return [
                    'operation' => 'success',
                    'area' => $model,
                    'country' => $model->country
                ];
            }
        }

        $url = $this->endPoint . 'place/details/json?placeid=' . $place_id;
        
        return Area::addByGoogleAPIResponse($url, $name, $country_name);
    }
}