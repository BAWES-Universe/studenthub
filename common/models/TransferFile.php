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
 * @property string $currency_code
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
            [['transfer_file_s3_path', "currency_code"], 'required'],
            [['currency_code'], "string", "max" => 3],
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
     * @param $insert
     * @return false|void
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) {
            return false;
        }

        if(!$this->currency_code) {
            $this->currency_code = "KWD";
        }

        return true;
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
            "currency_code" => Yii::t('app','Currency Code'),
        ];
    }
    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();
        
        $fields['totalCandidates'] = function ($model) {
            return (int) $model->getTransferCandidates()->count();
        };

        return $fields;
    }
    
    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'transferCandidates'
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
        $tf->currency_code = Yii::$app->request->getBodyParam('currency_code');

        if(!$tf->currency_code) {
            $tf->currency_code = Yii::$app->request->headers->get("Currency");
        }

        //get total amount marked as paid by this file 
        
        $tf->transfer_amount = TransferCandidate::find()
           ->select(new Expression('SUM((candidate_hourly_rate * hours) + bonus - bonus_commission)'))
           ->andWhere(['in', 'tc_id', $tc_ids])
           ->scalar();
             
        if($tf->save()) {
            TransferFile::transferMail($tf, count($tc_ids), $fileName);
            return $tf;
        }
    }

    /**
     * Send transfer file to accountant
     */
    public static function transferMail($transfer, $count, $fileName)
    {
        $url = Yii::$app->resourceManager->getUrl($transfer->transfer_file_s3_path);
        
        Yii::$app->mailer->htmlLayout = 'layouts/html';

        $amount = number_format($transfer->transfer_amount, 3);
        
        $mimeTypes = [
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'        
        ];
        
        $extension = pathinfo($fileName, PATHINFO_EXTENSION); 
                
        $subject = "Transferred {$transfer->currency_code} {$amount} to {$count} people";
        
        if(YII_ENV != 'prod') {
            $subject = '[Fake] [Ignore] ' . $subject;
        }

        $ml = new MailLog();
        $ml->to = \Yii::$app->params['finance_transfer'];
        $ml->from = \Yii::$app->params['supportEmail'];
        $ml->subject = $subject;
        $ml->save();

        Yii::$app->mailer->htmlLayout = "layouts/studenthub-html";

        $mailer = Yii::$app->mailer->compose("successfull-transfer",
            [
                "transfer" => $transfer,
                "count" => $count,
                'logo' => Yii::$app->urlManagerStaff->createUrl(
                    '../images/logo.png'
                )
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setCc([Yii::$app->params['operationsEmail']=>'operations'])
            ->setTo(Yii::$app->params['finance_transfer'])
            ->setSubject($subject)
            ->attachContent(file_get_contents($url), [
                'fileName' => $fileName, 
                'contentType' => $mimeTypes[$extension]
            ]);

        try {
            return $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "email_campaign");
        }
    }

    /**
     * populate transfer_file_entry table from transfer file
     * @throws \yii\db\Exception
     */
    public function populateEntries() {

        //read file

        $fileUrl = Yii::$app->resourceManager->getUrl ($this->transfer_file_s3_path);

        //save in temp folder to process

        $tmpFile = sys_get_temp_dir () . '/' . basename ($this->transfer_file_s3_path);

        if (!file_put_contents ($tmpFile, file_get_contents ($fileUrl))) {

            return false;/*[
                "operation" => "error",
                "type" => "system",
                "message" => "Error reading file"
            ];*/
        }

        $excelData = \moonland\phpexcel\Excel::import ($tmpFile, [
            'setFirstRecordAsKeys' => false
        ]);

        //remove first blank row

        \yii\helpers\ArrayHelper::remove ($excelData, '1');

        //second row will be key

        $keys = \yii\helpers\ArrayHelper::remove ($excelData, '2');

        //create array with key to read data

        $data = [];

        foreach ($excelData as $values) {
            $data[] = array_combine ($keys, $values);
        }

        //no need file anymore

        @unlink ($tmpFile);

        $transferFileEntries = [];

        foreach ($data as $key => $value) {

            //remove empty rows

            if (empty($value['Status'])) {
                continue;
            }

            $tfe_uuid = 'tfe_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

            $transferFileEntries[] = [
                'tfe_uuid' => $tfe_uuid,
                'transfer_file_id' => $this->transfer_file_id,
                'status' => $value['Status'],
                'status_description' => $value['Status Description'],
                'section_index' => $value['Section Index'],
                'transfer_method' => $value['Transfer Method'],
                'credit_amount' => str_replace (',', '', $value['Credit Amount']),
                'credit_currency' => $value['Credit Currency'],
                'exchange_rate' => (float) $value['Exchange Rate'],
                'dealRefNo' => $value['DealRefNo'],
                'value_date' => $value['Value Date'],
                'debit_account_no' => $value['Debit Account No'],
                'credit_account_no' => $value['Credit Account No'],
                'debit_narrative' => $value['Debit Narrative'],
                'credit_narrative' => $value['Credit Narrative'],
                'payment_details_1' => $value['Payment Details 1'],
                'payment_details_2' => $value['Payment Details 2'],
                'payment_details_3' => $value['Payment Details 3'],
                'payment_details_4' => $value['Payment Details 4'],
                'beneficiary_name' => $value['Beneficiary Name'],
                'beneficiary_address_line_1' => $value['Beneficiary Address Line 1'],
                'beneficiary_address_line_2' => $value['Beneficiary Address Line 2'],
                'beneficiary_bank_name' => $value['Beneficiary Bank Name'],
                'beneficiary_bank_address_1' => $value['Beneficiary Bank Address 1'],
                'beneficiary_bank_address_2' => $value['Beneficiary Bank Address 2'],
                'beneficiary_bank_address_3' => $value['Beneficiary Bank Address 3'],
                'swift' => $value['Swift'],
                'intermediary_account' => $value['Intermediary Account'],
                'intermediary_swift' => $value['Intermediary Swift'],
                'intrmediary_name' => $value['Intrmediary Name'],
                'intermediary_address_1' => $value['Intermediary Address 1'],
                'intermediary_address_2' => $value['Intermediary Address 2'],
                'intermediary_address_3' => $value['Intermediary Address 3'],
                'charges_type' => $value['Charges Type'],
                'sort_code' => $value['Sort Code'],
                'BIC_code' => $value['BIC Code'],
                'IBAN' => $value['IBAN'],
                'ABA_routing_code' => $value['ABA Routing Code'],
                //'created_by' => null,
                //'updated_by' => null,
                'created_at' => date ('Y-m-d'),
                'updated_at' => date ('Y-m-d'),
            ];
        }

        //populate entries

        $columns = [
            'tfe_uuid', 'transfer_file_id', 'status', 'status_description', 'section_index', 'transfer_method', 'credit_amount',
            'credit_currency', 'exchange_rate', 'dealRefNo', 'value_date', 'debit_account_no', 'credit_account_no', 'debit_narrative',
            'credit_narrative', 'payment_details_1', 'payment_details_2', 'payment_details_3', 'payment_details_4',
            'beneficiary_name', 'beneficiary_address_line_1', 'beneficiary_address_line_2', 'beneficiary_bank_name',
            'beneficiary_bank_address_1', 'beneficiary_bank_address_2', 'beneficiary_bank_address_3', 'swift', 'intermediary_account',
            'intermediary_swift', 'intrmediary_name', 'intermediary_address_1', 'intermediary_address_2', 'intermediary_address_3',
            'charges_type', 'sort_code', 'BIC_code', 'IBAN', 'ABA_routing_code', 'created_at', 'updated_at',
        ];

        Yii::$app->db->createCommand ()->batchInsert ('transfer_file_entry', $columns,
            $transferFileEntries
        )->execute ();

        return true;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferFileEntry($modelClass = "\common\models\TransferFileEntry")
    {
        return $this->hasMany($modelClass::className(), ['transfer_file_id' => 'transfer_file_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasMany($modelClass::className(), ['transfer_file_id' => 'transfer_file_id']);
    }
}
