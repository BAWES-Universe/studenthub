<?php

namespace common\components;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;
use yii\base\InvalidConfigException;

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
    
    private $configured = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->cloud_name = $this->normalizeConfigValue($this->cloud_name);
        $this->api_key = $this->normalizeConfigValue($this->api_key);
        $this->api_secret = $this->normalizeConfigValue($this->api_secret);

        $this->configured = $this->cloud_name !== null
            && $this->api_key !== null
            && $this->api_secret !== null;

        if (!$this->configured) {
            return;
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

    private function normalizeConfigValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function assertConfigured()
    {
        if (!$this->configured) {
            throw new InvalidConfigException('Cloudinary credentials are not configured.');
        }
    }

    /**
     * Upload image 
     * @param string $filePath
     * @param array $options
     * @return array
     */
    public function upload($filePath, $options) 
    {
        $this->assertConfigured();

        return (new UploadApi())->upload(
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
        $this->assertConfigured();

        //remove extension from path to get public_id
        
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        
        $public_id = str_replace(".".$ext, "", $path);
        //$this->cloudinary->delete

        $result = (new UploadApi())->destroy($public_id, [
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
        $this->assertConfigured();

        $result = (new AdminApi())->asset($public_id);

        if ($result['secure_url']) {
            return $result['secure_url'];
        }
        //return  ($public_id, ["resource_type" => $type]);
    }
}


