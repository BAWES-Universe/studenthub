<?php

namespace common\components;

use Aws;
use Aws\Credentials\CredentialProvider;
use Yii;
use Aws\S3\S3Client;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use Aws\MediaConvert\MediaConvertClient;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;


/**
 * S3ResourceManager handles resources to upload/uploaded to Amazon AWS
 *
 * Adjustments to the resource manager for BAWES usage
 * Up to date inspired from dosamigos\resourcemanager
 *
 * @author Khalid Al-Mutawa <khalid@bawes.net>
 * @link http://www.bawes.net
 */
class S3ResourceManager extends Component
{
    const AUTH_VIA_KEY_AND_SECRET = 1;
    const AUTH_VIA_IAM_ROLE = 2;
    const AUTH_VIA_ANONYMOUS = 3;

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
    public function init()
    {
        $this->assertRequiredAttributes();

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
    public function save($file, $name, $options = [], $source_file = null, $content_type = null)
    {
        if ($file) {
            $source_file = $file->tempName;
            $content_type = $file->type;

        }
        else if (gettype($source_file) == 'string' && strpos($source_file, 'http') > -1) { //if url
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

    public function saveContent($fileName, $content, $contentType) {
        $options = [
            'Bucket' => $this->bucket,
            'Key'    => $fileName,
            'Body'   => $content,
            'ACL' => 'public-read', // default to ACL public read
            'ContentType' => $contentType,
        ];

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
    public function copy($oldFile, $newFile, $sourceBucket = "", $options = [])
    {
        // Set Source bucket to the components defined bucket if none specified.
        $sourceBucket = $sourceBucket ? $sourceBucket : $this->bucket;

        $options = ArrayHelper::merge([
            'Bucket' => $this->bucket,
            'Key' => $newFile,
            'CopySource' => urlencode($sourceBucket . "/" . $oldFile),
            'ACL' => 'public-read', // default canned ACL; a private override needs s3:PutObjectAcl
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
     * Existence check with this component's own client.
     * On the anonymous temporary manager this cannot prove a private object exists.
     *
     * @param string $key
     * @return bool
     */
    public function storedObjectExists($key)
    {
        try {
            $this->getClient()->headObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param string $key
     * @return array{bytes: string, contentType: string}
     */
    public function readStoredObject($key)
    {
        $result = $this->getClient()->getObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);
        $type = isset($result['ContentType']) ? $result['ContentType'] : 'application/octet-stream';

        return [
            'bytes' => (string) $result['Body'],
            'contentType' => $type,
        ];
    }

    /**
     * Checks whether a file exists or not. This method only works for public resources, private resources will throw
     * a 403 error exception.
     * @param string $filenameOrUrl the name or url of the file
     * @return boolean
     */
    public function fileExists($filenameOrUrl)
    {
        $isUrl = false;
        if (strpos($filenameOrUrl, 'http') !== false) {
            $isUrl = true;
        }

        $http = $this->httpClient($isUrl ? $filenameOrUrl : $this->getUrl($filenameOrUrl));
        try {
            $response = $http->request('HEAD');
        } catch (\Exception $e) {
            return false;
        }
        return $response->getStatusCode() == 200;
    }

    const ANONYMOUS_PUBLIC = 'public';
    const ANONYMOUS_NOT_PUBLIC = 'not-public';
    const ANONYMOUS_UNCERTAIN = 'uncertain';

    /** @var array{category: string, status: int|null}|null */
    private $anonymousPrivacyLog;

    /**
     * Anonymous HEAD of the object URL built from this bucket and key.
     * A filename containing "http" is still that key. It is not used as a URL.
     * 200 is public. 403 and 404 are not public. Transport failures, timeouts,
     * and any other status are uncertain and must not be treated as private.
     *
     * S3 authorizes this HEAD with s3:GetObject.
     *
     * @param string $key
     * @return string public|not-public|uncertain
     */
    public function anonymousObjectPrivacy($key)
    {
        try {
            $url = $this->getUrl((string) $key);
            $response = $this->httpClient($url)->request('HEAD', '', [
                'timeout' => 4,
                'connect_timeout' => 2,
                'http_errors' => false,
                'allow_redirects' => false,
            ]);
        } catch (\Throwable $e) {
            $category = $this->anonymousFailureCategory($e);
            $this->recordAnonymousPrivacy($category, null);

            return self::ANONYMOUS_UNCERTAIN;
        }

        $status = (int) $response->getStatusCode();
        if ($status === 200) {
            return self::ANONYMOUS_PUBLIC;
        }
        if ($status === 403 || $status === 404) {
            return self::ANONYMOUS_NOT_PUBLIC;
        }

        $this->recordAnonymousPrivacy('unexpected', $status);

        return self::ANONYMOUS_UNCERTAIN;
    }

    /**
     * @return array{category: string, status: int|null}|null
     */
    public function anonymousPrivacyLog()
    {
        return $this->anonymousPrivacyLog;
    }

    /**
     * The exception message can contain a URL, so only the category is kept.
     * Curl errno 28 is the bounded connect or request timeout.
     *
     * @param \Throwable $e
     * @return string
     */
    private function anonymousFailureCategory(\Throwable $e)
    {
        if (method_exists($e, 'getHandlerContext')) {
            $context = $e->getHandlerContext();
            if (is_array($context) && isset($context['errno']) && (int) $context['errno'] === 28) {
                return 'timeout';
            }
        }

        return 'transport';
    }

    /**
     * @param string $category
     * @param int|null $status
     * @return void
     */
    private function recordAnonymousPrivacy($category, $status)
    {
        $status = $status === null ? null : (int) $status;
        $this->anonymousPrivacyLog = [
            'category' => (string) $category,
            'status' => $status,
        ];
        if (!class_exists(\Yii::class, false) || \Yii::$app === null) {
            return;
        }
        $statusText = $status === null ? 'none' : (string) $status;
        \Yii::warning(
            'anonymous licence privacy ' . $category . ' status=' . $statusText,
            'company'
        );
    }

    /**
     * Authenticated HEAD with this component's client against an explicit bucket.
     * Private temporary licences call this on the permanent resourceManager
     * and pass the temporary bucket. S3 authorizes that HEAD with s3:GetObject.
     * Copying the object with an explicit ACL also needs s3:PutObject and
     * s3:PutObjectAcl on studenthub-uploads.
     *
     * @param string $bucket
     * @param string $key
     * @return bool
     */
    public function objectExistsInBucket($bucket, $key)
    {
        try {
            $this->getClient()->headObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->recordAuthenticatedExistenceFailure($e);

            return false;
        }
    }

    /**
     * The exception message can contain a key or a request URL.
     * A 404 is the normal missing-object result and is not logged.
     *
     * @param \Throwable $e
     * @return void
     */
    private function recordAuthenticatedExistenceFailure(\Throwable $e)
    {
        $category = 'transport';
        $status = null;
        if ($e instanceof \Aws\Exception\AwsException) {
            $status = $e->getStatusCode();
            if ($status === 404) {
                return;
            }
            if ($status === 403) {
                $category = 'access-denied';
            } else {
                $code = $e->getAwsErrorCode();
                if (is_string($code) && preg_match('/^[A-Za-z0-9]+$/', $code)) {
                    $category = $code;
                } else {
                    $category = 'unexpected';
                }
            }
        }
        if (!class_exists(\Yii::class, false) || \Yii::$app === null) {
            return;
        }
        $statusText = $status === null ? 'none' : (string) (int) $status;
        \Yii::warning(
            'authenticated object check ' . $category . ' status=' . $statusText,
            'company'
        );
    }

    /**
     * Returns the url of the file or empty string if the file does not exists.
     * @param string $name the key name of the file to access
     * @param mixed $expires The time at which the URL should expire
     * @return string
     */
    public function getUrl($name, $expires = NULL)
    {
        // The installed SDK returns an unsigned object URL and accepts only
        // bucket and key. Callers do not pass $expires.
        return $this->getClient()->getObjectUrl($this->bucket, $name);
    }

    /**
     * Return file detail
     * @param string $filenameOrUrl The file name or URL of the S3 object
     * @return string file mime type
     */
    public function getHeaders($filenameOrUrl)
    {
        $isUrl = false;
        if (strpos($filenameOrUrl, 'http') !== false) {
            $isUrl = true;
        }

        $http = $this->httpClient($isUrl ? $filenameOrUrl : $this->getUrl($filenameOrUrl));

        try {
            $response = $http->request('HEAD');
        } catch (\Exception $e) {
            return false;
        }
        return $response->getHeaders();
    }

    /**
     * Gets type of the object in filename or url
     * @param string $filenameOrUrl The file name or URL of the S3 object
     * @return string file mime type
     */
    public function getType($filenameOrUrl)
    {
        $headers = $this->getHeaders($filenameOrUrl);

        return $headers['Content-Type'][0];
    }

    /**
     * Gets size of the object in filename or url
     * @param string $filenameOrUrl The file name or URL of the S3 object
     * @return integer           the file size
     */
    public function getSize($filenameOrUrl)
    {
        $headers = $this->getHeaders($filenameOrUrl);

        return $headers['Content-Length'][0];
    }

    /**
     * Gets file duration from meta data
     * @param string $filenameOrUrl The file name or URL of the S3 object
     * @return integer           the file duration
     */
    public function getDuration($filenameOrUrl)
    {
        $headers = $this->getHeaders($filenameOrUrl);

        return isset($headers['x-amz-meta-duration']) ? $headers['x-amz-meta-duration'][0] : null;
    }

    /**
     * Returns a S3Client instance
     * @return \Aws\S3\S3Client
     */
    public function getClient()
    {
        if ($this->_client === null) {
            $factoryParams = [
                'version' => 'latest',
                'region' => $this->region
            ];

            // Key-and-secret passes an explicit pair. Anonymous passes false.
            // Omitting credentials would search the default provider chain.
            if ($this->authMethod == self::AUTH_VIA_KEY_AND_SECRET) {
                $this->assertRequiredAttributes();
                $factoryParams['credentials'] = [
                    'key' => $this->key,
                    'secret' => $this->secret,
                ];
            } elseif ($this->authMethod == self::AUTH_VIA_ANONYMOUS) {
                $this->assertRequiredAttributes();
                $factoryParams['credentials'] = false;
            }

            $this->_client = new S3Client($factoryParams);
        }
        return $this->_client;
    }

    /**
     * @param string $baseUri
     * @return \GuzzleHttp\Client
     */
    protected function httpClient($baseUri)
    {
        return new \GuzzleHttp\Client(['base_uri' => $baseUri]);
    }

    /**
     * Reject missing credentials before the AWS SDK can choose its own.
     * The exception names the empty field and does not include the value.
     */
    private function assertRequiredAttributes()
    {
        $requiredAttributes = ['region', 'bucket'];
        if ($this->authMethod == self::AUTH_VIA_KEY_AND_SECRET) {
            $requiredAttributes = ['key', 'secret', 'region', 'bucket'];
        }

        foreach ($requiredAttributes as $attribute) {
            $value = $this->$attribute;
            $blankCredential = ($attribute === 'key' || $attribute === 'secret')
                && is_string($value)
                && trim($value) === '';
            if ($value === null || $blankCredential) {
                throw new InvalidConfigException(strtr('"{class}::{attribute}" cannot be empty.', [
                    '{class}' => static::class,
                    '{attribute}' => '$' . $attribute
                ]));
            }
        }
    }


}
