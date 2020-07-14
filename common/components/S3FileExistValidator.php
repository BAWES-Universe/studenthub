<?php
namespace common\components;

use Yii;
use yii\validators\Validator;

/**
 * S3FileExistValidator will validate if the attribute contains a filename of an object within
 * your bucket
 */
class S3FileExistValidator extends Validator
{
    /**
     * @var \common\components\S3ResourceManager The S3 resource manager containing bucket, key, and secret
     */
    public $resourceManager;
    
    /**
     * @var string The file path to check for within the bucket
     * By default it will check main bucket location
     * Make sure to include slash at end path
     * eg: uploads/
     */
    public $filePath = "";
    
    /**
     * File extensions allowed 
     * @var type 
     */
    public $extensions;
    
    /**
     * Max file size allowed
     * @var type 
     */
    public $maxSize;
    
    /**
     * @var string the error message to be shown if validation fails
     */
    public $message = "Uploaded file does not exist";
    
    public function validateAttribute($model, $attribute)
    {
        $filename = $model->$attribute;
        
        if(!$filename || !$this->resourceManager) {
            return null; 
        }
        
        //check if this file exists within this resourceManager bucket

        if(!$this->resourceManager->fileExists($this->filePath.$filename))
        {
            $this->addError($model, $attribute, Yii::t('app', $this->message));
        }

        //if allowd extensions defined 
        
        $allowdExtensions = explode(',', str_replace('.', '', $this->extensions)); 
        
        if($allowdExtensions) 
        {
            $extension = pathinfo($filename, PATHINFO_EXTENSION); 

            if(!in_array(strtolower($extension), $allowdExtensions) &&
                    !in_array(strtoupper($extension), $allowdExtensions)) 
            {
                $this->addError($model, $attribute, Yii::t('app', 'Invalid file type'));
            }
        }
        
        //if max size defined 
        
        if($this->maxSize) 
        {
            $size = $this->resourceManager->getSize($filename);

            if($this->maxSize < $size) 
            {
                $this->addError($model, $attribute, Yii::t('app', 'Max allowed file size is {size}', [
                    'size' => $this->maxSize
                ]));
            }
        }   
    }
}