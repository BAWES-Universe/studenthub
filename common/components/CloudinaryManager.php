<?php

namespace common\components;

use Yii; 
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

/**
 *
 * Adjustments to the Cloudinary images
 * 
 * @author Khalid Al-Mutawa <khalid@bawes.net>
 * @link http://www.bawes.net
 */
class CloudinaryManager {

    public $cloud_name;
    public $api_key;
    public $api_secret;
    
    private $cloudinary;

    public function __construct($cloud_name, $api_key, $api_secret) {
        $this->cloud_name = $cloud_name;
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;

        // Configure Cloudinary
        $config = Configuration::instance([
            'cloud' => [
                "cloud_name" => $this->cloud_name,
                "api_key" => $this->api_key,
                "api_secret" => $this->api_secret
            ],
        ]);

        $this->cloudinary = new Cloudinary($config);

    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach (['cloud_name', 'api_key', 'api_secret'] as $attribute) {
            if ($this->$attribute === null) {
                throw new yii\base\InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::class,
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }
//        parent::init();

    }

    /**
     * Upload image 
     * @param string $filePath
     * @param array $options
     * @return array
     */
    public function upload($filePath, $options) 
    {
        $config = Configuration::instance([
            'cloud' => [
                "cloud_name" => $this->cloud_name,
                "api_key" => $this->api_key,
                "api_secret" => $this->api_secret
            ],
        ]);

        $this->cloudinary = new Cloudinary($config);
        
        return ($this->cloudinary->uploadApi())->upload(
            $filePath, 
            $options
        );
    }
    
    /**
     * Delete image
     * @param string $path
     * @return array
     */
    public function delete($path, $type = "image")
    {
        //remove extension from path to get public_id
        
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        
        $public_id = str_replace(".".$ext, "", $path);
        //$this->cloudinary->delete

        $result = ($this->cloudinary->uploadApi())->destroy($public_id, [
            "invalidate" => true,//remove from CDN cache if any
            "resource_type" => $type
        ]);
        
        return $result;
    }
    
    /**
     * Get image url by public_id
     * @param string $public_id
     * @return array
     */
    public function getUrl($public_id, $type = "image")
    {
        $result = $this->cloudinary->adminApi()->asset($public_id);

        if ($result['secure_url']) {
            return $result['secure_url'];
        }
        //return  ($public_id, ["resource_type" => $type]);
    }
}


