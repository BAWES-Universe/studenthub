<?php

namespace common\components;

use Aws\S3\S3Client;
use Aws\S3\Enum\CannedAcl;
use GuzzleHttp\Exception\ClientException;
use yii\helpers\Html;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use Yii;

/**
 *
 * Adjustments to the resource manager
 *
 * @author Khalid Al-Mutawa <khalid@bawes.net>
 * @link http://www.bawes.net
 */
class S3ResourceManager extends Component {

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
	 * @var string Amazon Bucket Region
	 */
	public $region;
	/**
	 * @var \Aws\S3\S3Client
	 */
	private $_client;


    /**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		foreach (['key', 'secret', 'bucket'] as $attribute) {
			if ($this->$attribute === null) {
				throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
					'{class}' => static::className(),
					'{attribute}' => '$' . $attribute
				]));
			}
		}
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

        if($file) {
            $source_file = $file->tempName;
            $content_type = $file->type;
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
        $sourceBucket = $sourceBucket? $sourceBucket : $this->bucket;

        $options = ArrayHelper::merge([
                    'Bucket' => $this->bucket,
                    'Key' => $newFile,
                    'CopySource' => Html::encode($sourceBucket."/".$oldFile),
                    'ACL' => 'public-read', // default to ACL public read - allows public to open file
                    ], $options);

        return $this->getClient()->copyObject($options);
    }

    /**
	 * Removes a file
	 * @param string $name the name of the file to remove
	 * @return boolean
	 */
	public function delete($name)
	{
		$result = $this->getClient()->deleteObject([
			'Bucket' => $this->bucket,
			'Key' => $name
		]);

		return $result['DeleteMarker'];
	}

    /**
	 * Checks whether a file exists or not. This method only works for public resources, private resources will throw
	 * a 403 error exception.
	 * @param string $name the name of the file
	 * @return boolean
	 */
	public function fileExists($name)
	{
		$http = new \GuzzleHttp\Client();
		try {
			$response = $http->get($this->getUrl($name));
		} catch(ClientException $e) {
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
	public function getUrl($name, $expires = NULL)
	{
		return $this->getClient()->getObjectUrl($this->bucket, $name, $expires);
	}

    /**
	 * Delete all objects that match a specific key prefix.
	 * @param string $prefix delete only objects under this key prefix
	 * @return type
	 */
	public function deleteMatchingObjects($prefix) {
		return $this->getClient()->deleteMatchingObjects($this->bucket, $prefix);
	}

    /**
	 * Return the full path a file names only (no directories) within s3 virtual "directory" by treating s3 keys as path names.
	 * @param string $directory the prefix of keys to find
	 * @return array of ['path' => string, 'name' => string, 'type' => string, 'size' => int]
	 */
	public function listFiles($directory) {
		$files = [];

		$iterator = $this->getClient()->getIterator('ListObjects', [
			'Bucket' => $this->bucket,
			'Prefix' => $directory,
		]);

		foreach ($iterator as $object) {
			// don't return directories
			if(substr($object['Key'], -1) != '/') {
				$file = [
					'path' => $object['Key'],
					'name' => substr($object['Key'], strrpos($object['Key'], '/' ) + 1),
					'type' => $object['StorageClass'],
					'size' => (int)$object['Size'],
				];
				$files[] = $file;
			}
		}

		return $files;
	}

    /**
	 * Returns a S3Client instance
	 * @return \Aws\S3\S3Client
	 */
	public function getClient()
	{
		if ($this->_client === null) {
			$settings=[
                'version' => 'latest',
                'region' => $this->region,
                'signature' => 'v4',
                'credentials' => [
        			'key' => $this->key,
        			'secret' => $this->secret
                ]
			];

			$this->_client = S3Client::factory($settings);
		}
		return $this->_client;
	}

}
