<?php

namespace common\models;

use admin\models\Transfer;
use admin\models\TransferCandidate;
use Yii;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;


/**
 * This is the model class for table "transfer_file".
 *
 * @property int $transfer_file_id
 * @property string $bank
 * @property string $transfer_file_s3_path
 * @property string $transfer_amount
 * @property string $currency_code
 * @property string $transfer_file_created_at
 * @property string $transfer_file_updated_at
 * @property string $error
 * @property number $status
 * @property number $admin_id
 * @property TransferCandidate $tc
 */
class TransferFile extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_FAILED = 1;
    const STATUS_PROCESSED = 2;
    const STATUS_PROCESSING = 3;

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
            [['bank'], "string", "max" => 100],
            ['status', 'in', 'range' => [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_FAILED, self::STATUS_PROCESSED]],
            [['transfer_file_created_at', 'transfer_file_updated_at', 'transfer_amount'], 'safe'],
            [['transfer_file_s3_path', "error"], 'string', 'max' => 255],
            [['admin_id'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['admin_id' => 'admin_id']],

        ];
    }
      
    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
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
            "bank" => Yii::t('app', 'Bank'),
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

        $fields['status'] = function ($model) {
            return (int) $model->status;
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
    public static function saveFile($tc_ids, $fileName, $bank = "AUB") {

        $sourceBucket = Yii::$app->temporaryBucketResourceManager->bucket;
        
        $targetPath = "transfer-files/".$fileName;

        // Copy using S3ResourceManager Component
        Yii::$app->resourceManager->copy($fileName, $targetPath, $sourceBucket);

        $tf = new TransferFile();
        $tf->admin_id = Yii::$app->user->getId();
        $tf->transfer_file_s3_path = $targetPath;
        $tf->currency_code = Yii::$app->request->getBodyParam('currency_code', "KWD");
        $tf->bank = $bank;

        if(!$tf->currency_code) {
            $tf->currency_code = Yii::$app->request->headers->get("Currency", "KWD");
        }

        //get total amount marked as paid by this file 

        $tf->transfer_amount = TransferCandidate::find()
           ->select(new Expression('SUM(candidate_total)'))
            //(candidate_hourly_rate * hours) + bonus - bonus_commission
           ->andWhere(['in', 'tc_id', $tc_ids])
           ->scalar();
             
        if($tf->save()) {
            return $tf;
        }

        return null;
    }

    /**
     * process transfer file via cron job
     * @return void
     * @throws \yii\db\Exception
     */
    public function process() {

        //mark as processing
        $this->status = self::STATUS_PROCESSING;
        if (!$this->save(false)) {
            echo "Error updating trasfer file status :" . print_r($this->getErrors()) . "\n";
            Yii::error("Error updating trasfer file status :" . print_r($this->getErrors()));
            die();
        }

        $transaction = Yii::$app->db->beginTransaction();

        //mark candidates as paid

        $data = [];

        if ($this->bank == "BankStatement") {
            $data = $this->populateFromBankStatement($transaction);
        } else  if ($this->bank) {
            $data = $this->populateEntries($transaction);
        } else {
            $data = $this->populateEntriesForManual($transaction);
        }

        if (!$data) {
            $transaction->rollBack();

            $this->markFailed("no data");

            echo "No data \n";

            die();
        }

        $tc_ids = \yii\helpers\ArrayHelper::getColumn($data, 'tc_id');

        $transferCandidates = TransferCandidate::find()
            ->andWhere(['in', 'tc_id', $tc_ids])
            ->all();

        $transferCandidatesMapped = \yii\helpers\ArrayHelper::index($transferCandidates, 'tc_id');

        foreach ($data as $value)
        {
            // if tc_id from request body not found in transfer candidate db table

            if(empty($transferCandidatesMapped[$value['tc_id']]))
            {
                $transaction->rollBack();

                echo "Invalid request \n";

                $this->markFailed('Invalid request');

                die();
            }

            $tc = $transferCandidatesMapped[$value['tc_id']];

            $tc->setScenario(TransferCandidate::SCENARIO_MARKING_PAID);
            $tc->paid = 1;
            $tc->transfer_file_id = $this->transfer_file_id;
            $tc->transfer_confirmation_id = $value['transfer_confirmation_id'];

            //validation adding extra overhead in system

            if(!$tc->update(false)) //false
            {
                echo "Error updating candidate transfer: #" . $value['tc_id'] . " ".
                    print_r($tc->getErrors(), true) . "\n";

                $transaction->rollBack();

                $this->markFailed("Error updating candidate transfer: #" . $value['tc_id'] . " ".
                    json_encode($tc->getErrors()));
            }

            for ($i = 0; $i < 3; $i++) {

                try {

                    // Execute your query
                    if(!$tc->update())
                    {
                        $transaction->rollBack();

                        echo "Error updating candidate transfer: #" . $value['tc_id'] . " ".
                            json_encode($tc->getErrors()) . "\n";

                        $this->markFailed("Error updating candidate transfer: #" . $value['tc_id'] . " ".
                            json_encode($tc->getErrors()));

                        /*return [
                            "operation" => "error",
                            "code" => 5,
                            "transfer_confirmation_id" => $value['transfer_confirmation_id'],
                            "transfer_file_id" => $this->transfer_file_id,
                            "message" => $tc->getErrors()
                        ];*/

                        die();
                    }

                    break; // Exit loop if successful
                } catch (Exception $e) {

                    if ($e->getCode() == 1213) { // Deadlock
                        echo "Sleeping for 1 second \n";
                        sleep(1); // Brief pause before retry
                        continue;
                    }
                    throw $e; // Rethrow other exceptions
                }
            }

            echo "Candidate transfer marked as paid #" . $value['tc_id'] . "\n";

            //todo: this can make it slow
            $tc->emailTransferSuccess();
        }

        // Check if all paid, mark transfer as complete

        $transfer_ids = array_unique(
            \yii\helpers\ArrayHelper::getColumn($data, 'transfer_id')
        );

        foreach($transfer_ids as $transfer_id) {
            echo "Transfer marked as paid #" . $transfer_id . "\n";
            Transfer::markTransferCompleteOnCandidatePaid($transfer_id);
        }

        //save transfer file entries

        //processing from cron
        /*if ($bank) {
            try {
                $tf->populateEntries();
            } catch (\yii\db\Exception $e) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    'message' => $e->getMessage(),
                ];
            }
        } else {
            $tf->populateEntriesForManual();
        }*/

        if ($this->admin) {
            Yii::info('[' . count($data) . ' candidates have been marked as paid]  By ' . $this->admin->admin_name, __METHOD__);
        } else {
            Yii::info('[' . count($data) . ' candidates have been marked as paid]  By admin', __METHOD__);
        }
//Yii::$app->user->identity->admin_name

        $this->markProcessed(count($data), basename($this->transfer_file_s3_path));

        $transaction->commit();

        if(YII_ENV == 'prod') {
            echo "Transfer Payable Candidate Event \n";

            Transfer::triggerPayableCandidateEvent();
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

        if(\Yii::$app->params['elasticMailIpPool']) {
            $mailer->setHeader ("poolName", \Yii::$app->params['elasticMailIpPool']);
        }

        try {
            return $mailer->send();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Handle email transport-specific exceptions
            Yii::error( "Failed to send email: " . $e->getMessage());
        } catch (\Exception $e) {
            // Handle any other exceptions
            Yii::error( "An error occurred: " . $e->getMessage());
        }
    }

    /**
     * populate transfer_file_entry table from transfer file
     * @throws \yii\db\Exception
     */
    public function populateEntries($transaction) {

        //read file

        $fileUrl = Yii::$app->resourceManager->getUrl ($this->transfer_file_s3_path);

        //save in temp folder to process

        $tmpFile = sys_get_temp_dir () . '/' . basename ($this->transfer_file_s3_path);

        if (!file_put_contents ($tmpFile, file_get_contents ($fileUrl))) {

            $transaction->rollBack();

            $this->markFailed("Error reading file");

            Yii::error("Error reading file");

            die();
           /*[
                "operation" => "error",
                "type" => "system",
                "message" => "Error reading file"
            ];*/
        }

        $excelData = \common\components\PhpExcel::import ($tmpFile, [
            'setFirstRecordAsKeys' => false
        ]);

        //remove first blank row

        $totalRowsToRemove = $this->bank == "AUB" ? 1: 8;

        for ($i = 1; $i <  $totalRowsToRemove + 1; $i++) {
            \yii\helpers\ArrayHelper::remove($excelData, $i);
        }

        //second row will be key

        $keyIndex = $this->bank == "AUB"? 2: 9;

        $keys = \yii\helpers\ArrayHelper::remove ($excelData, $keyIndex);

        //create array with key to read data

        $data = [];

        foreach ($excelData as $values) {
            $data[] = array_combine ($keys, $values);
        }

        //no need file anymore

        @unlink ($tmpFile);

        $transferFileEntries = [];

        $candidatesTransfers = [];

        foreach ($data as $key => $value) {

            //remove empty rows

            if (
                ($this->bank == "AUB" && empty($value['Status'])) ||
                ($this->bank == "KFH" && empty($value['Refrence Number']))
            ) {
                continue;
            }

            $tfe_uuid = 'tfe_' . Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

            if ($this->bank == "AUB") {

                $transferCandidate = TransferCandidate::find()
                    ->andWhere(['tc_id' => $value['Credit Narrative']])
                    ->one();

                if(!$transferCandidate || !$transferCandidate->candidate) {

                    $transaction->rollBack();

                    $this->markFailed("Invalid excel");

                    Yii::error('Invalid excel');

                    die();
                }

                $candidatesTransfers[] = [
                    'transfer_confirmation_id' => $value['Status Description'],
                    'transfer_id' => $value['Debit Narrative'],
                    'tc_id' => $value['Credit Narrative'],
                    "tc_created_at" => $transferCandidate->tc_created_at,
                    'candidate_name' => $transferCandidate->candidate->candidate_name,
                    "candidate_id" => $transferCandidate->candidate_id,
                    'total_amount' => $transferCandidate->totalPaidToCandidate,
                    "currency_code" => $transferCandidate->currency_code
                ];

                $transferFileEntries[] = [
                    'tfe_uuid' => $tfe_uuid,
                    'transfer_file_id' => $this->transfer_file_id,
                    'status' => $value['Status'],
                    'status_description' => $value['Status Description'],
                    'section_index' => $value['Section Index'],
                    'transfer_method' => $value['Transfer Method'],
                    'credit_amount' => str_replace(',', '', $value['Credit Amount']),
                    'credit_currency' => $value['Credit Currency'],
                    'exchange_rate' => (float)$value['Exchange Rate'],
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
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                ];
            } else {

                $transferCandidate = TransferCandidate::find()
                    ->andWhere([
                        'transfer_benef_iban' => $value['Beneficiary Account'],
                        "candidate_total" => $value['Amount'],
                        "currency_code" => $value['Transfer Currency'], // good to have filter, if same bank account in 2 country
                        "paid" => 0
                    ])
                    // having latest transfern as can have same bank account (for duplicate profile), same amount + currency (for previous month's transfer),
                    ->orderBy("tc_id DESC")
                    ->one();

                if(!$transferCandidate) {

                    $transaction->rollBack();

                    $this->markFailed("No unpaid transfer found with Beneficiary Account: " . $value['Beneficiary Account'].
                        " Amount: " . $value['Amount Deducted']);

                    Yii::error("No unpaid transfer found with Beneficiary Account: " . $value['Beneficiary Account'].
                        " Amount: " . $value['Amount Deducted']);

                    die();
                }

                if (!$transferCandidate->candidate) {

                    $transaction->rollBack();

                    $this->markFailed("No candidate profile found with Beneficiary Account: " . $value['Beneficiary Account'].
                        " Amount: " . $value['Amount Deducted']);

                    Yii::error("No candidate profile found with Beneficiary Account: " . $value['Beneficiary Account'].
                        " Amount: " . $value['Amount Deducted']);

                    die();
                }

                $candidatesTransfers[] = [
                    'transfer_confirmation_id' => $value['Refrence Number'],
                    "paid" => $transferCandidate->paid,
                    'transfer_id' => $transferCandidate->transfer_id,
                    'tc_id' => $transferCandidate->tc_id,
                    "tc_created_at" => $transferCandidate->tc_created_at,
                   // 'candidate_name' => $transferCandidate->candidate->candidate_name,
                    "candidate_id" => $transferCandidate->candidate_id,
                    'total_amount' => $value['Amount'],//$transferCandidate->totalPaidToCandidate,
                    "currency_code" => $value['Transfer Currency'], //$transferCandidate->currency_code
                ];

                //if ($this->bank == "KFH")
                $transferFileEntries[] = [
                    'tfe_uuid' => $tfe_uuid,
                    'transfer_file_id' => $this->transfer_file_id,
                    'status' => "SUCCESS",
                    'status_description' => $value['Refrence Number'],
                    'credit_amount' => str_replace (',', '', $value['Amount']),
                    'credit_currency' => $value['Transfer Currency'],
                    'exchange_rate' => (float) $value['Exchange Rates'],
                    'value_date' => null,// from excel?
                    'debit_account_no' => null,
                    'credit_account_no' => $value['Beneficiary Account'],
                    'debit_narrative' => null, //fetch from db?
                    'credit_narrative' => null, //fetch from db?
                    'beneficiary_name' => $value['Beneficiary Name'],
                    'beneficiary_bank_name' => $value['Bank'],
                    'created_at' => date ('Y-m-d'),
                    'updated_at' => date ('Y-m-d'),
                ];
            }
        }

        //populate entries

        $columns = $this->bank == "AUB" ? [
            'tfe_uuid', 'transfer_file_id', 'status', 'status_description', 'section_index', 'transfer_method', 'credit_amount',
            'credit_currency', 'exchange_rate', 'dealRefNo', 'value_date', 'debit_account_no', 'credit_account_no', 'debit_narrative',
            'credit_narrative', 'payment_details_1', 'payment_details_2', 'payment_details_3', 'payment_details_4',
            'beneficiary_name', 'beneficiary_address_line_1', 'beneficiary_address_line_2', 'beneficiary_bank_name',
            'beneficiary_bank_address_1', 'beneficiary_bank_address_2', 'beneficiary_bank_address_3', 'swift', 'intermediary_account',
            'intermediary_swift', 'intrmediary_name', 'intermediary_address_1', 'intermediary_address_2', 'intermediary_address_3',
            'charges_type', 'sort_code', 'BIC_code', 'IBAN', 'ABA_routing_code', 'created_at', 'updated_at',
        ]: [
            'tfe_uuid',
            'transfer_file_id',
            'status',
            'status_description',
            'credit_amount',
            'credit_currency',
            'exchange_rate',
            'value_date',
            'debit_account_no',
            'credit_account_no',
            'debit_narrative',
            'credit_narrative',
            'beneficiary_name',
            'beneficiary_bank_name',
            'created_at',
            'updated_at'
        ];

        Yii::$app->db->createCommand ()->batchInsert ('transfer_file_entry', $columns,
            $transferFileEntries
        )->execute ();

        return $candidatesTransfers;
    }

    public function markFailed($error) {
        $this->status = self::STATUS_FAILED;
        $this->error = $error;
        $this->update(false);
    }

    public function markProcessed($count, $fileName) {

        $this->status = self::STATUS_PROCESSED;
        $this->update(false);

        TransferFile::transferMail($this, $count, $fileName);
    }

    /**
     * @param $transaction
     * @return array|void
     * @throws \yii\db\Exception
     */
    public function populateFromBankStatement($transaction) {

        $fileUrl = Yii::$app->resourceManager->getUrl ($this->transfer_file_s3_path);

        //save in temp folder to process

        $tmpFile = sys_get_temp_dir () . '/' . basename ($this->transfer_file_s3_path);

        if (!file_put_contents ($tmpFile, file_get_contents ($fileUrl))) {

            $transaction->rollBack();

            $this->markFailed("Error reading file");

            Yii::error("Error reading file");

            die();
        }

        $excelData = \common\components\PhpExcel::import ($tmpFile, [
            'setFirstRecordAsKeys' => false
        ]);

        //remove 7 title row

        for ($i = 1; $i < 8; $i++) {
            \yii\helpers\ArrayHelper::remove($excelData, $i);
        }

        //9th row will be key

        $keys = \yii\helpers\ArrayHelper::remove($excelData, '8');

        if(empty($keys)) {

            $transaction->rollBack();

            $this->markFailed("Error reading bank statement file");

            Yii::error("Error reading bank statement");

            die();

            /*return [
                "operation" => "error",
                "type" => "system",
                "errorCode" => 3,
                "message" => ""
            ];*/
        }

        if (!isset($keys['Description'])) {

            Yii::error("Invalid file format for bank statement");

            $transaction->rollBack();

            $this->markFailed("Invalid file format for bank statement");

            die();
        }

        //create array with key to read data

        $data = [];

        foreach ($excelData as $values) {
            $data[] = array_combine ($keys, $values);
        }

        //no need file anymore

        @unlink ($tmpFile);

        $transferFileEntries = [];

        $candidatesTransfers = [];

        foreach ($data as $key => $value) {

            //extract candidate transfer id

            // Define the regex pattern to match a number after "SALARY"
            $pattern = '/SALARY\s+(\d+)/';

            // Initialize a variable to store the extracted number
            $tc_id = null;

            // Perform the regex match
            if (preg_match($pattern, $value['Description'], $matches)) {
                // The first capturing group contains the number after "SALARY"
                $tc_id = $matches[1];
            }

            if (!$tc_id) {
                continue;
            }

            $transferCandidate = TransferCandidate::find()
                ->andWhere([
                    'tc_id' => $tc_id,
                    "paid" => 0
                ])
                ->one();

            if(!$transferCandidate || !$transferCandidate->candidate) {
                /*return [
                    'operation' => 'error',
                    'message' => "No candidate profile found with Candidate Account: " . $value['Candidate ID'].
                        " Amount: " . $value['Candidate Total'],
                    'errorCode' => 4
                ];*/

                $transaction->rollBack();

                $this->markFailed("No candidate profile found for candidate transfer : " . $tc_id);

                Yii::error("No candidate profile found for candidate transfer: " . $tc_id);

                die();
            }

            //get reference number
            //example: IB/LOCAL TRANSFER/O-000004206364/MARIAN AKRAM MAGDY HABIB/BILL SETTLEMENT/SALARY 88467 000004206364

            $data = $value['Description']? explode("/", $value['Description']): null;

            $candidatesTransfers[] = [
                'transfer_confirmation_id' => isset($data[2])? $data[2]: $data[0],
                "paid" => $transferCandidate->paid,
                'transfer_id' => $transferCandidate->transfer_id,
                'tc_id' => $transferCandidate->tc_id,
                "tc_created_at" => $transferCandidate->tc_created_at,
                'candidate_name' => $transferCandidate->candidate->candidate_name,
                "candidate_id" => $transferCandidate->candidate_id,
                'total_amount' => (float) $transferCandidate->totalPaidToCandidate,
                "currency_code" => $transferCandidate->currency_code,
                "debited_amount" =>  isset($value['Debit']) ? (float)$value['Debit']: null,
                "credited_amount" => isset($value['Credit']) ? (float) $value['Credit']: null,
            ];

            //remove empty rows

            $tfe_uuid = 'tfe_' . $this->uuidv4();
            //Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

            $transferFileEntries[] = [
                'tfe_uuid' => $tfe_uuid,
                'transfer_file_id' => $this->transfer_file_id,
                'status' => "SUCCESS",
                'status_description' => $value['Description'],
                'credit_amount' => isset($value['Debit']) ? (float) $value['Debit']: null,
                'credit_currency' => $transferCandidate->currency_code,
                'debit_narrative' => $transferCandidate->transfer_id,
                'credit_narrative' => $transferCandidate->tc_id,
                'beneficiary_name' => isset($data[3])? $data[3]: $transferCandidate->candidate->candidate_name,
                'created_at' => date ('Y-m-d'),
                'updated_at' => date ('Y-m-d'),
            ];
        }

        //populate entries

        $columns = [
            'tfe_uuid', 'transfer_file_id', 'status', 'status_description', 'credit_amount',
            'credit_currency', 'debit_narrative',
            'credit_narrative', 'beneficiary_name', 'created_at', 'updated_at',
        ];

        Yii::$app->db->createCommand ()->batchInsert ('transfer_file_entry', $columns,
            $transferFileEntries
        )->execute ();

        return $candidatesTransfers;
    }

    public function populateEntriesForManual($transaction) {

        //read file

        $fileUrl = Yii::$app->resourceManager->getUrl ($this->transfer_file_s3_path);

        //save in temp folder to process

        $tmpFile = sys_get_temp_dir () . '/' . basename ($this->transfer_file_s3_path);

        if (!file_put_contents ($tmpFile, file_get_contents ($fileUrl))) {

            Yii::error("Error reading file");

            $transaction->rollBack();

            $this->markFailed("Error reading file");

            die();
        }

        $excelData = \common\components\PhpExcel::import ($tmpFile, [
            'setFirstRecordAsKeys' => false
        ]);

        //remove first blank row

        /*$totalRowsToRemove = 1;

        for ($i = 1; $i < $totalRowsToRemove + 1; $i++) {
            \yii\helpers\ArrayHelper::remove($excelData, $i);
        }*/

        //second row will be key

        $keyIndex = 1;

        $keys = \yii\helpers\ArrayHelper::remove ($excelData, $keyIndex);

        //create array with key to read data

        $data = [];

        foreach ($excelData as $values) {
            $data[] = array_combine ($keys, $values);
        }

        //no need file anymore

        @unlink ($tmpFile);

        $transferFileEntries = [];

        $candidatesTransfers = [];

        foreach ($data as $key => $value) {

            if(empty($value['Reference Number']) || !in_array(trim($value['Paid']), ["Yes", "YES"])) {
                continue;//ignore empty values
            }

            $transferCandidate = TransferCandidate::find()
                //->joinWith(['candidate'])
                ->andWhere([
                    //'transfer_benef_iban' => $value['Beneficiary Account'],
                    "transfer_candidate.candidate_id" => trim($value['Candidate ID']),
                    "transfer_candidate.candidate_total" => trim($value['Candidate Total']),
                    "transfer_candidate.currency_code" => trim($value['Currency Code']), // good to have filter, if same bank account in 2 country
                    "transfer_candidate.paid" => 0
                ])->orderBy("tc_id DESC")
                ->one();

            if (!$transferCandidate->candidate) {
                /*return [
                    'operation' => 'error',
                    'message' => "No candidate profile found with Candidate Account: " . $value['Candidate ID'].
                        " Amount: " . $value['Candidate Total'],
                    'errorCode' => 4
                ];*/

                $transaction->rollBack();

                $this->markFailed("No candidate profile found with Candidate Account: " . $value['Candidate ID'].
                    " Amount: " . $value['Candidate Total']);

                Yii::error("No candidate profile found with Candidate Account: " . $value['Candidate ID'].
                    " Amount: " . $value['Candidate Total']);

                die();
            }

            $candidatesTransfers[] = [
                'transfer_confirmation_id' => trim($value['Reference Number']),
                "paid" => $transferCandidate->paid,
                'transfer_id' => $transferCandidate->transfer_id,
                'tc_id' => $transferCandidate->tc_id,
                "tc_created_at" => $transferCandidate->tc_created_at,
                //'candidate_name' => $transferCandidate->candidate->candidate_name,
                "candidate_id" => $transferCandidate->candidate_id,
                'total_amount' => $value['Candidate Total'],//$transferCandidate->totalPaidToCandidate,
                "currency_code" => $value['Currency Code'], //$transferCandidate->currency_code
            ];

            //remove empty rows

            $tfe_uuid = 'tfe_' . $this->uuidv4();
                //Yii::$app->db->createCommand ('SELECT uuid()')->queryScalar ();

                $transferFileEntries[] = [
                    'tfe_uuid' => $tfe_uuid,
                    'transfer_file_id' => $this->transfer_file_id,
                    'status' => "SUCCESS",
                    'status_description' => $value['Reference Number'],
                    'credit_amount' => str_replace (',', '', $value['Candidate Total']),
                    'credit_currency' => $value['Currency Code'],
                    'debit_narrative' => null, //fetch from db?
                    'credit_narrative' => null, //fetch from db?
                    'beneficiary_name' => $value['Beneficiary Name'],
                    'created_at' => date ('Y-m-d'),
                    'updated_at' => date ('Y-m-d'),
                ];
        }

        //populate entries

        $columns = [
            'tfe_uuid', 'transfer_file_id', 'status', 'status_description', 'credit_amount',
            'credit_currency', 'debit_narrative',
            'credit_narrative', 'beneficiary_name', 'created_at', 'updated_at',
        ];

        Yii::$app->db->createCommand ()->batchInsert ('transfer_file_entry', $columns,
            $transferFileEntries
        )->execute ();

        return $candidatesTransfers;
    }

    /**
     * generate uuid for primary key
     * @return string
     * @throws \Exception
     */
    public function uuidv4() {
        /* 32 random HEX + space for 4 hyphens */
        $out = bin2hex(random_bytes(18));

        $out[8]  = "-";
        $out[13] = "-";
        $out[18] = "-";
        $out[23] = "-";

        /* UUID v4 */
        $out[14] = "4";

        /* variant 1 - 10xx */
        $out[19] = ["8", "9", "a", "b"][random_int(0, 3)];

        return $out;
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

    /**
     * @param $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getAdmin($modelClass = "\common\models\Admin")
    {
        return $this->hasOne($modelClass::className(), ['admin_id' => 'admin_id']);
    }
}
