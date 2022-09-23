<?php

namespace common\components;


class JiraComponent
{
    public $jiraUrl;
    public $email;
    public $apiToken;

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach (['jiraUrl', 'email', 'apiToken'] as $attribute) {
            if ($this->$attribute === null) {
                throw new yii\base\InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::className(),
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }

        //parent::init();
    }

    public function getApiEndpointUrl()
    {
        return rtrim($this->jiraUrl, '/') . '/rest/api/3/';
    }

    public function getUrlOfPath($path)
    {
        return $this->getApiEndpointUrl() . ltrim($path, '/');
    }

    public function get($path, $params = [])
    {
        if (!empty($params)) {
            $params = http_build_query($params);
            $path .= '?' . $params;
        }

        return $this->request('GET', $path);
    }

    public function post($path, $body = [])
    {
        return $this->request('POST', $path, $body);
    }

    public function delete($path, $body = [])
    {
        return $this->request('DELETE', $path, $body);
    }

    public function put($path, $body = [])
    {
        return $this->request('PUT', $path, $body);
    }

    public function getProject($key)
    {
        $data = $this->get("project/{$key}");
        if (!isset($data['id'])) {
            return null;
        } else {
            return Project::populate($this, $data);
        }
    }

    public function request($method, $path, $body = [])
    {
        $url = $this->getUrlOfPath($path);

        /*if (is_array($body) && !empty($body)) {
            $body = Json::encode($body);
        }

        $cacheKey = md5($method . $url. $body);

        $result = Yii::$app->cache->get($cacheKey);

        if ($result !== false)
        {
            return $result;
        }*/

            //$authString = base64_encode($this->email . ':' . $this->apiToken);
            $authString = base64_encode('kk@bawes.net' . ':'. $this->apiToken);//.
            //$request->addHeader("Authorization", "Basic " . $authString);

            /*$client = new Client();

            return $client->createRequest()
                ->setUrl($url)
                ->setMethod($method)
                ->addHeaders([
                    'authorization' => 'Basic '.$authString,//Bearer
                    'content-type' => 'application/json',
                    "Accept" => "application/json"
                    //"Upgrade" => "HTTP/2.0, SHTTP/1.3, IRC/6.9, RTA/x11",
                    //"HTTP/2.0"
                ])
                ->send();*/

        //Yii::$app->cache->set($cacheKey, $result, $this->cacheDuration);

        //set POST variables

        $fields = array('from' => 'markdown',
            'to' => 'pdf',
            'input_files[]' => "@/".realpath('markdown.md').";type=text/x-markdown; charset=UTF-8"
        );

//open connection
        $ch = curl_init();

//set options
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            //'Authorization' => 'Basic '.$authString,//
            'content-type' => 'application/json',
            "Accept" => "application/json"
            //"Upgrade" => "HTTP/2.0, SHTTP/1.3, IRC/6.9, RTA/x11",
            //"HTTP/2.0"
        ]);
        //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($ch, CURLOPT_USERPWD, $this->email . ':'  );//

        curl_setopt($ch, CURLOPT_USERPWD, $this->email . ":" . $this->apiToken);

        //curl_setopt($ch, CURLOPT_HTTPHEADER,          [         	"Authorization: Basic ".base64_encode($this->username.":".$this->password),         ]);

        curl_setopt($ch, CURLOPT_URL, $url);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //needed so that the $result=curl_exec() output is the file and isn't just true/false

        //$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code

//execute post
        $result = curl_exec($ch);

//close connection
        curl_close($ch);

        return json_decode ($result);
    }
}