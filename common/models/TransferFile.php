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
 * @property string $transfer_amount
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
            [['transfer_file_created_at', 'transfer_file_updated_at', 'transfer_amount'], 'safe'],
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
            'transfer_amount' => Yii::t('app', 'Transfer Amount'),
            'transfer_file_created_at' => Yii::t('app', 'Transfer File Created At'),
            'transfer_file_updated_at' => Yii::t('app', 'Transfer File Updated At'),
        ];
    }
    
    /**
     * save excel used to mark transfers as paid
     * @param array $tc_ids
     * @param string $fileName
     */
    public static function saveFile($tc_ids, $fileName) {

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
        
        $targetPath = "transfer-files/".$fileName;

        // Copy using S3ResourceManager Component
        Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        $tf = new TransferFile();
        $tf->transfer_file_s3_path = $targetPath;
        
        //get total amount marked as paid by this file 
        
        $tf->transfer_amount = TransferCandidate::find()
           ->select(new Expression('SUM((candidate_hourly_rate * hours) + bonus - bonus_commission)'))
           ->filterWhere(['in', 'tc_id', $tc_ids])
           ->scalar();
             
        if($tf->save()) {
            TransferFile::transferMail($tf, count($tc_ids), $fileName);
            return $tf->transfer_file_id;
        }
    }

    /**
     * Send transfer file to accountant
     */
    public static function transferMail($transfer, $count, $fileName)
    {
        $url = "https://studenthub-uploads-dev-server.s3.amazonaws.com/". $transfer->transfer_file_s3_path;
        
        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $amount = $transfer->transfer_amount;
        
        $mimeTypes = [
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'        
        ];
        
        $extension = pathinfo($fileName, PATHINFO_EXTENSION); 
                
        return Yii::$app->mailer->compose("successfull-transfer",
            [
                "transfer" => $transfer,
                'file' => $url,
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => 'StudentHub'])
            ->setTo(Yii::$app->params['finance_transfer'])
            ->setSubject("[StudentHub] Transferred {$amount} KD to {$count} people")
            ->attachContent(file_get_contents($url), [
                'fileName' => $fileName, 
                'contentType' => $mimeTypes[$extension]
            ])
            ->send();
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasOne($modelClass::className(), ['transfer_file_id' => 'transfer_file_id']);
    }
}
