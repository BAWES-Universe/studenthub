<?php

namespace common\models;

use common\models\BalanceAccount;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "transfer".
 *
 * @property string $transfer_uuid
 * @property string $transfer_uuid_short
 * @property string|null $user_uuid
 * @property string|null $bank_uuid
 * @property string|null $transfer_confirmation_id
 * @property string|null $transfer_file_uuid
 * @property string|null $transfer_benef_name
 * @property string|null $transfer_benef_iban
 * @property float|null $transfer_cost
 * @property float|null $transfer_total
 * @property int|null $transfer_status
 * @property string|null $transfer_created_at
 * @property string|null $transfer_updated_at
 *
 * @property Bank $bankUu
 * @property TransferFileEntry[] $transferFileEntries
 * @property TransferFile $transferFileUu
 * @property User $userUu
 */
class WalletTransfer extends \yii\db\ActiveRecord
{
    const STATUS_IN_PROGRESS = 3;
    const STATUS_TRANSFER_COMPLETE = 4;
    const STATUS_INITIATED = 10; // Draft

    public function getTransferStatus() {
        switch ($this->transfer_status) {
            case 3:
                return "In Progress";
            case 4:
                return "Complete";
            default:
                return "Initiated";
        }
    }

    /**
     * @return array
     */
    public static function statusList()
    {
        return [
            self::STATUS_INITIATED => 'Initiated',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_TRANSFER_COMPLETE => 'Transfer Completed',
        ];
    }

    public static function getDb()
    {
        return \Yii::$app->walletDb;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transfer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_uuid', 'bank_uuid', 'transfer_benef_name', 'transfer_benef_iban', 'transfer_cost',
                'transfer_total', 'transfer_status'], 'required'],
            [['transfer_cost', 'transfer_total'], 'number'],
            [['transfer_status'], 'integer'],
            [['transfer_created_at', 'transfer_updated_at'], 'safe'],
            [['transfer_uuid', 'user_uuid', 'bank_uuid', 'transfer_file_uuid', 'transfer_benef_name'], 'string', 'max' => 60],
            [['transfer_uuid_short'], 'string', 'max' => 35],
            [['transfer_confirmation_id'], 'string', 'max' => 128],
            [['transfer_benef_iban'], 'string', 'max' => 50],
            [['transfer_uuid', 'transfer_uuid_short'], 'unique'],
            ['transfer_total', 'validateTotal'],
            [['bank_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => WalletBank::className(), 'targetAttribute' => ['bank_uuid' => 'bank_uuid']],
            [['transfer_file_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => TransferFile::className(), 'targetAttribute' => ['transfer_file_uuid' => 'transfer_file_uuid']],
            [['user_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => WalletUser::className(), 'targetAttribute' => ['user_uuid' => 'user_uuid']],
        ];
    }

    public function validateTotal()
    {
        $account = \common\models\BalanceAccount::find()
            ->andWhere([
                'account_uuid' => $this->user_uuid,
                'type' => BalanceAccount::TYPE_USER_PAYABLE
            ])
            ->one();

        if(!$account) {
            $account = new BalanceAccount;
            $account->account_uuid = $this->user_uuid;
            $account->type = BalanceAccount::TYPE_USER_PAYABLE;
            $account->save();
        }

        /*$pendingAmount = Transfer::find()
            ->andWhere(['user_uuid' => $this->user_uuid])
            ->andWhere(['!=', 'transfer_status', Transfer::STATUS_TRANSFER_COMPLETE])
            ->sum('transfer_total');*/

        if($this->transfer_total > $account->balance)
        {
            //$amount = $account->balance;

            $this->addError('transfer_total','Transfer total can not be more ' . $account->balance);
        }
    }

    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'transfer_uuid',
                ],
                'value' => function() {
                    if (!$this->transfer_uuid)
                        $this->transfer_uuid = 'transfer_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->transfer_uuid;
                }
            ],
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'transfer_uuid_short',
                ],
                'value' => function() {
                    if (!$this->transfer_uuid_short)
                        $this->transfer_uuid_short = 'transfer_' . Yii::$app->db->createCommand('SELECT uuid_short()')->queryScalar();

                    return $this->transfer_uuid_short;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'transfer_created_at',
                'updatedAtAttribute' => 'transfer_updated_at',
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
            'transfer_uuid' => Yii::t('app', 'Transfer Uuid'),
            'transfer_uuid_short' => Yii::t('app', 'Transfer Short Uuid'),
            'user_uuid' => Yii::t('app', 'User Uuid'),
            'bank_uuid' => Yii::t('app', 'Bank Uuid'),
            'transfer_confirmation_id' => Yii::t('app', 'Transfer Confirmation ID'),
            'transfer_file_uuid' => Yii::t('app', 'Transfer File Uuid'),
            'transfer_benef_name' => Yii::t('app', 'Transfer Benef Name'),
            'transfer_benef_iban' => Yii::t('app', 'Transfer Benef Iban'),
            'transfer_cost' => Yii::t('app', 'Transfer Cost'),
            'transfer_total' => Yii::t('app', 'Transfer Total'),
            'transfer_status' => Yii::t('app', 'Transfer Status'),
            'transfer_created_at' => Yii::t('app', 'Transfer Created At'),
            'transfer_updated_at' => Yii::t('app', 'Transfer Updated At'),
        ];
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if($insert)
        {
            $account = \common\models\BalanceAccount::find()
                ->andWhere([
                    'account_uuid' => $this->user_uuid,
                    'type' => BalanceAccount::TYPE_USER_PAYABLE
                ])
                ->one();

            $account->addEntry(
                $this->user,
                0 - $this->transfer_total,
                "Transfer Initiated"
            );
        }
    }

    /**
     * get list of transferable candidate
     * for text export
     * @return array
     */
    public static function getPayableAdvice()
    {
        $totalAmount = 0;

        $transfers = self::find()
            ->payable()
            ->havingBankInfo()
            ->all();

        if (!$transfers) {
            return false;
        }

        $list = [];

        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile
        foreach ($transfers as $transfer) {

            if (
                empty($transfer->bank) ||
                !$transfer->bank_uuid ||
                !$transfer->transfer_benef_iban ||
                !$transfer->transfer_benef_name
            ) {
                continue;
            }

            //todo: differece between candidate_total and totalPaidToCandidate

            $totalAmount += $transfer->transfer_total;

            $list[] = [
                'Section Index' => 'D',
                'Reference Number' => $transfer->transfer_uuid_short,//Debit Narrative 1
                'Email ID' => $transfer->user->email,
                'Invoice Date' => date('dmY'),
                'Invoice Info' => $transfer->transfer_uuid,
                //'Invoice No' => $transferCandidate->tc_id,
                'Invoice Currency' => $transfer->currency_code,
                'Invoice Amount' => number_format($transfer->transfer_total, 3, '.', '')
            ];
        }

        return [
            'user_list' => $list,
            'total_amount' => number_format($totalAmount, 3, '.', ''),
        ];
    }

    /**
     * get list of transferable
     * for text export
     * @return array
     */
    public static function getPayableListFormat()
    {
        $totalAmount = 0;

        $transfers = self::find()
            ->payable()
            ->havingBankInfo()
            ->all();

        if (!$transfers) {
            return false;
        }

        $list = [];

        //https://www.pivotaltracker.com/story/show/176535038
        // to force users to complete there profile
        foreach ($transfers as $transfer) {

            if (
                empty($transfer->bank) ||
                !$transfer->bank_uuid ||
                !$transfer->transfer_benef_iban ||
                !$transfer->transfer_benef_name
            ) {
                continue;
            }

            //todo: differece between candidate_total and totalPaidToCandidate

            $totalAmount += $transfer->transfer_total;

            $list[] = [
                'transfer' => 'S2',
                'bank_transfer_type' => $transfer->bank->bank_transfer_type,
                'amount' => number_format($transfer->transfer_total, 3, '.', ''),
                'currency' => empty($transfer->currency_code) ? "KWD" : $transfer->currency_code,
                'emptyField1' => '',
                'emptyField2' => '',
                'emptyField3' => '',
                'Field1' => '11622216',
                'iban' => ltrim(rtrim($transfer->transfer_benef_iban)),
                'Debit Narrative' => $transfer->transfer_uuid_short,
                'Credit Narrative' => 1,//just for format, same as $transferCandidate->tc_id, can use $transfer->user_uuid
                'description' => 'From BAWES Wallet',
                'emptyField4' => '',
                'emptyField5' => '',
                'emptyField6' => '',
                'bank_account_name' => ltrim(rtrim($transfer->transfer_benef_name)),
                'bank_name' => $transfer->bank->bank_name,
                'emptyField7' => '',
                'bank_name_repeat' => $transfer->bank->bank_name,
                'bank_address' => $transfer->bank->bank_address,
                'emptyField8' => '',
                'emptyField9' => '',
                'bank_swift_code' => $transfer->bank->bank_swift_code,
                'emptyField10' => '',
                'emptyField11' => '',
                'emptyField12' => '',
                'emptyField13' => '',
                'emptyField14' => '',
                'emptyField15' => '',
                'Field2' => 'B',
                'emptyField16' => '',
                'emptyField17' => '',
                'candidate_iban' => ltrim(rtrim($transfer->transfer_benef_iban))
            ];
        }

        return [
            'user_list' => $list,
            'total_amount' => number_format($totalAmount, 3, '.', ''),
        ];
    }

    /**
     * Total amount that will be sent to the user
     * @return string
     */
    public function getTotalPaidToUser()
    {
        if(!isset(Yii::$app->params['transfer_cost'])) {
            Yii::$app->params['transfer_cost'] = 0;
        }

        //+ Yii::$app->params['transfer_cost'] we bearing transfer cost?

        return round(
            $this->transfer_total,
            3
        );
    }

    /**
     * total amount paid
     * @return bool|int|mixed|string|null
     */
    public function getTransferFileTotal() {
        return TransferFileEntry::find()
            ->andWhere(['debit_narrative' => $this->transfer_uuid])
            ->sum('credit_amount');
    }

    //todo: unpaidNotification()

    /**
     * Gets query for [[BankUu]]
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBank($modelClass = "\common\models\WalletBank")
    {
        return $this->hasOne($modelClass::className(), ['bank_uuid' => 'bank_uuid']);
    }

    /**
     * Gets query for [[UserUu]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser($modelClass = "\common\models\WalletUser")
    {
        return $this->hasOne($modelClass::className(), ['user_uuid' => 'user_uuid']);
    }

    /**
     * @inheritdoc
     * @return query\TransferQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\WalletTransferQuery(get_called_class());
    }
}
