<?php

namespace common\components;

use Yii; 
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;

/**
 *
 * Adjustments to the Cloudinary images
 * 
 * @author Khalid Al-Mutawa <khalid@bawes.net>
 * @link http://www.bawes.net
 */
class CloudinaryManager extends \yii\base\Component {

    public $cloud_name;
    public $api_key;
    public $api_secret;
    
    private $cloudinary;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        foreach (['cloud_name', 'api_key', 'api_secret'] as $attribute) {
            if ($this->$attribute === null) {
                throw new yii\base\InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::class,
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }

        /*define('CLOUDINARY_CLOUD_NAME', $this->cloud_name);
        define('CLOUDINARY_API_KEY', $this->api_key);
        define('CLOUDINARY_API_SECRET', $this->api_secret);
*/
        Configuration::instance([
            'cloud' => [
                "cloud_name" => $this->cloud_name,
                "api_key" => $this->api_key,
                "api_secret" => $this->api_secret
            ], 
            'url' => [
                'secure' => true
            ]
        ]);
    }

    /**
     * Upload image 
     * @param string $filePath
     * @param array $options
     * @return array
     */
    public function upload($filePath, $options) 
    {
        return (new uploadApi())->upload(
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

        $result = (new uploadApi())->destroy($public_id, [
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
        $result = (new adminApi())->asset($public_id);

        if ($result['secure_url']) {
            return $result['secure_url'];
        }
        //return  ($public_id, ["resource_type" => $type]);
    }
}


