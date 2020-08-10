<?php

namespace common\models;

use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;


/**
 * This is the model class for table "transfer_file".
 *
 * @property int $transfer_file_id
 * @property string $transfer_file_s3_path
 * @property string $transfer_file_created_at
 * @property string $transfer_file_updated_at
 *
 * @property TransferCandidate $tc
 */
class TransferFile extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transfer_file';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transfer_file_s3_path'], 'required'],
            [['transfer_file_created_at', 'transfer_file_updated_at'], 'safe'],
            [['transfer_file_s3_path'], 'string', 'max' => 255],
        ];
    }
      
    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'transfer_file_created_at',
                'updatedAtAttribute' => 'transfer_file_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'transfer_file_id' => Yii::t('app', 'Transfer File ID'),
            'transfer_file_s3_path' => Yii::t('app', 'Transfer File S3 Path'),
            'transfer_file_created_at' => Yii::t('app', 'Transfer File Created At'),
            'transfer_file_updated_at' => Yii::t('app', 'Transfer File Updated At'),
        ];
    }
    
    /**
     * save excel used to mark transfers as paid
     * @param type $fileName
     */
    public static function saveFile($fileName) {

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
        
        $targetPath = "transfer-files/".$fileName;

        // Copy using S3ResourceManager Component
        Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        $tf = new TransferFile();
        $tf->transfer_file_s3_path = $targetPath;
        
        if($tf->save()) {
            return $tf->transfer_file_id;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasOne($modelClass::className(), ['transfer_file_id' => 'transfer_file_id']);
    }
}
