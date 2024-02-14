<?php

namespace common\components;

use common\models\Country;
use common\models\Currency;
use GuzzleHttp\Exception\ClientException;
use Yii;

class Ipstack {

    // Prefix to the IP cache object
//    public $ipPrefix = "client_ip:";
    // IP Cache duration
//    public $cacheDuration = 1 * 60 * 60; //1 hours
    public $accessKey;

    public function locate() {
        // Get initial IP address of requester
        $ip = Yii::$app->request->getRemoteIP();

        // Check if request is forwarded via load balancer or cloudfront on behalf of user
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'];

            // as "X-Forwarded-For" is usually a list of IP addresses that have routed
            $IParray = array_values(array_filter(explode(',', $forwardedFor)));

            // Get the first ip from forwarded array to get original requester
            $ip = $IParray[0];
        }

        // Build url used for ip check
        $url = 'https://api.ipstack.com/' . $ip . '?access_key=' . $this->accessKey;

        // Check if calling from localhost
        if ($ip == '::1' || $ip == '127.0.0.1') {
            $url = 'https://api.ipstack.com/check?access_key=' . $this->accessKey;
        }

        // Return IP info from cache OR make a request for new data then cache
        //return Yii::$app->cache->getOrSet($this->ipPrefix.$ip, function () use ($url) {
        // Initiate the request object
        $http = new \GuzzleHttp\Client(['base_uri' => $url]);

        try {
            $responseObj = $http->request('GET');
        } catch (ClientException $e) {
            return false;
        }

        $result = \GuzzleHttp\json_decode($responseObj->getBody()->getContents());

        //Fix: https://www.pivotaltracker.com/story/show/165662472

        if (!isset($result->city)) {
            if ($result->region_name)
                $result->city = $result->region_name;
            else if ($result->location && $result->location->capital)
                $result->city = $result->location->capital;
            else
                $result->city = $result->continent_name;
        }

        // save latest records from our table

        if (isset($result->currency->code)) {

            $currency = Currency::find()
                ->where(['code' => $result->currency->code])
                ->one();

            if($currency) {
                $result->currency = $currency;
            }
        }

        if (isset($result->country_code)) {

            $country = Country::find()
                ->where(['iso' => $result->country_code])
                ->one();

            $result->country = $country;
        }

        return $result;

        //}, $this->cacheDuration);
    }
}
