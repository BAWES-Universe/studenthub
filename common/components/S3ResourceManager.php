<?php

namespace common\components;

use Aws\S3\Enum\CannedAcl;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use dosamigos\resourcemanager\AmazonS3ResourceManager;

/**
 *
 * Adjustments to the resource manager
 * 
 * @author Khalid Al-Mutawa <khalid@bawes.net>
 * @link http://www.bawes.net
 */
class S3ResourceManager extends AmazonS3ResourceManager {

    /**
     * Saves a file
     * @param \yii\web\UploadedFile $file the file uploaded. The [[UploadedFile::$tempName]] will be used as the source
     * file.
     * @param string $name the name of the file
     * @param array $options extra options for the object to save on the bucket. For more information, please visit
     * [[http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.S3.S3Client.html#_putObject]]
     * @return \Guzzle\Service\Resource\Model
     */
    public function save($file, $name, $options = []) {
        $options = ArrayHelper::merge([
                    'Bucket' => $this->bucket,
                    'Key' => $name,
                    'SourceFile' => $file->tempName,
                    'ACL' => CannedAcl::PUBLIC_READ, // default to ACL public read
                    'ContentType' => $file->type,
                ], $options);

        return $this->getClient()->putObject($options);
    }

    /**
     * Creates a copy of a file from old key to new key
     * @param string $oldFile old file name / path that you wish to copy
     * @param string $newFile target destination for file name / path
     * @param array $options
     * @return \Guzzle\Service\Resource\Model
     */
    public function copy($oldFile, $newFile, $options = []) {
        
        $options = ArrayHelper::merge([
                    'Bucket' => $this->bucket,
                    'Key' => $newFile,
                    'CopySource' => Html::encode($this->bucket."/".$oldFile),
                    'ACL' => CannedAcl::PUBLIC_READ, // default to ACL public read - allows public to open file
                    ], $options);

        return $this->getClient()->copyObject($options);
                        
    }

}
