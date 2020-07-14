<?php

namespace common\components;

use yii\helpers\Html;
use Aws\S3\S3Client;
use GuzzleHttp\Exception\ClientErrorResponseException;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * S3ResourceManager handles resources to upload/uploaded to Amazon AWS
 *
 * Adjustments to the resource manager for BAWES usage
 * Up to date inspired from dosamigos\resourcemanager
 *
 * @author Khalid Al-Mutawa <khalid@bawes.net>
 * @link http://www.bawes.net
 */
class S3ResourceManager extends Component {

    const AUTH_VIA_KEY_AND_SECRET = 1;
    const AUTH_VIA_IAM_ROLE = 2;

    /**
     * @var string Auth Method
     */
    public $authMethod = self::AUTH_VIA_KEY_AND_SECRET;

    /**
     * @var string Amazon access key
     */
    public $key;

    /**
     * @var string Amazon secret access key
     */
    public $secret;

    /**
     * @var string Amazon Bucket
     */
    public $bucket;

    /**
     * @var string AWS Region this bucket belongs in
     */
    public $region;

    /**
     * @var \Aws\S3\S3Client
     */
    private $_client;

    /**
     * @inheritdoc
     */
    public function init() {
        // Fields required by default
        $requiredAttributes = ['region', 'bucket'];

        // If Auth via Key and Secret, set vars as required
        if ($this->authMethod == self::AUTH_VIA_KEY_AND_SECRET) {
            $requiredAttributes = ['key', 'secret', 'region', 'bucket'];
        }

        // Process Validation
        foreach ($requiredAttributes as $attribute) {
            if ($this->$attribute === null) {
                throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::className(),
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }

        parent::init();
    }

    /**
     * Saves a file
     * @param \yii\web\UploadedFile $file the file uploaded. The [[UploadedFile::$tempName]] will be used as the source
     * file.
     * @param string $name the name of the file
     * @param array $options extra options for the object to save on the bucket. For more information, please visit
     * [[http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.S3.S3Client.html#_putObject]]
     * @return \Guzzle\Service\Resource\Model
     */
    public function save($file, $name, $options = [], $source_file = null, $content_type = null) {

        if ($file) {
            $source_file = $file->tempName;
            $content_type = $file->type;
        } else {
            $source_file = urlencode($source_file);
        }

        $options = ArrayHelper::merge([
            'Bucket' => $this->bucket,
            'Key' => $name,
            'SourceFile' => $source_file,
            'ACL' => 'public-read', // default to ACL public read
            'ContentType' => $content_type,
        ], $options);

        return $this->getClient()->putObject($options);
    }

    /**
     * Creates a copy of a file from old key to new key
     * @param string $oldFile old file name / path that you wish to copy
     * @param string $newFile target destination for file name / path
     * @param string $sourceBucket the bucket to copy the file from
     * @param array $options
     * @return \Guzzle\Service\Resource\Model
     */
    public function copy($oldFile, $newFile, $sourceBucket = "", $options = []) {
        // Set Source bucket to the components defined bucket if none specified.
        $sourceBucket = $sourceBucket ? $sourceBucket : $this->bucket;

        $options = ArrayHelper::merge([
                    'Bucket' => $this->bucket,
                    'Key' => $newFile,
                    'CopySource' => urlencode($sourceBucket . "/" . $oldFile),
                    'ACL' => 'public-read', // default to ACL public read - allows public to open file
        ], $options);

        return $this->getClient()->copyObject($options);
    }

    /**
     * Removes a file
     * @param string $name the name of the file to remove
     * @return boolean
     */
    public function delete($name) {
        $result = $this->getClient()->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $name
        ]);

        return $result['DeleteMarker'];
    }

    /**
     * Checks whether a file exists or not. This method only works for public resources, private resources will throw
     * a 403 error exception.
     * @param string $filenameOrUrl the name or url of the file
     * @return boolean
     */
    public function fileExists($filenameOrUrl) {
        $isUrl = false;
        if (strpos($filenameOrUrl, 'http') !== false) {
            $isUrl = true;
        }

        $http = new \GuzzleHttp\Client(['base_uri' => $isUrl ? $filenameOrUrl : $this->getUrl($filenameOrUrl)]);
        try {
            $response = $http->request('HEAD');
        } catch (\Exception $e) {
            return false;
        }
        return $response->getStatusCode() == 200;
    }

    /**
     * Returns the url of the file or empty string if the file does not exists.
     * @param string $name the key name of the file to access
     * @param mixed $expires The time at which the URL should expire
     * @return string
     */
    public function getUrl($name, $expires = NULL) {
        return $this->getClient()->getObjectUrl($this->bucket, $name, $expires);
    }

    /**
     * Return file detail 
     * @param  string $filenameOrUrl The file name or URL of the S3 object
     * @return string file mime type 
     */
    public function getHeaders($filenameOrUrl) {
        $isUrl = false;
        if (strpos($filenameOrUrl, 'http') !== false) {
            $isUrl = true;
        }

        $http = new \GuzzleHttp\Client(['base_uri' => $isUrl ? $filenameOrUrl : $this->getUrl($filenameOrUrl)]);
        try {
            $response = $http->request('HEAD');
        } catch (\Exception $e) {
            return false;
        }
        return $response->getHeaders();
    }
    
    /**
     * Gets size of the object in filename or url
     * @param  string $filenameOrUrl The file name or URL of the S3 object
     * @return string file mime type 
     */
    public function getType($filenameOrUrl) {
        $headers = $this->getHeaders($filenameOrUrl);
        
        return $headers['Content-Type'][0];
    }
    
    /**
     * Gets size of the object in filename or url
     * @param  string $filenameOrUrl The file name or URL of the S3 object
     * @return integer           the file size
     */
    public function getSize($filenameOrUrl) {
        $headers = $this->getHeaders($filenameOrUrl);
        
        return $headers['Content-Length'][0];
    }

    /**
     * Returns a S3Client instance
     * @return \Aws\S3\S3Client
     */
    public function getClient() {
        if ($this->_client === null) {
            $factoryParams = [
                'version' => 'latest',
                'region' => $this->region
            ];

            // Use key and secret if its the auth method
            if ($this->authMethod == self::AUTH_VIA_KEY_AND_SECRET) {
                $factoryParams['credentials'] = [
                    'key' => $this->key,
                    'secret' => $this->secret,
                ];
            }

            // Create S3 client instance
            $this->_client = S3Client::factory($factoryParams);
        }
        return $this->_client;
    }

}
