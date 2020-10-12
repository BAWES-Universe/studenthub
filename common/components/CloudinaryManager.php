<?php

namespace common\components;

use Yii; 


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
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach (['cloud_name', 'api_key', 'api_secret'] as $attribute) {
            if ($this->$attribute === null) {
                throw new yii\base\InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::className(),
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
        \Cloudinary::config(array( 
          "cloud_name" => $this->cloud_name,
          "api_key" => $this->api_key,
          "api_secret" => $this->api_secret
        ));
    
        return \Cloudinary\Uploader::upload(
            $filePath, 
            $options
        );
    }
    
    /**
     * Delete image
     * @param type $path
     * @return type
     */
    public function delete($path, $type = "image")
    {
        \Cloudinary::config(array( 
          "cloud_name" => $this->cloud_name,
          "api_key" => $this->api_key,
          "api_secret" => $this->api_secret
        ));
        
        //remove extension from path to get public_id
        
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        
        $public_id = str_replace(".".$ext, "", $path);
        
        $result = \Cloudinary\Uploader::destroy($public_id, ["resource_type" => $type]);
        
        return $result;
    }
    
    /**
     * Get image url by public_id
     * @param type $public_id
     * @return type
     */
    public function getUrl($public_id, $type = "image")
    {   
        \Cloudinary::config(array( 
          "cloud_name" => $this->cloud_name,
          "api_key" => $this->api_key,
          "api_secret" => $this->api_secret
        ));
        return cloudinary_url($public_id,["resource_type" => $type]);
    }
}


