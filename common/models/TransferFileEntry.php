<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "transfer_file_entries".
 *
 * @property string $tfe_uuid
 * @property int $transfer_file_id
 * @property string $status
 * @property string $status_description
 * @property string $section_index
 * @property string $transfer_method
 * @property string $credit_amount
 * @property string $credit_currency
 * @property string $exchange_rate
 * @property string $dealRefNo
 * @property string $value_date
 * @property string $debit_account_no
 * @property string $credit_account_no
 * @property int $debit_narrative transfer_id
 * @property int $credit_narrative tc_id
 * @property string $payment_details_1
 * @property string $payment_details_2
 * @property string $payment_details_3
 * @property string $payment_details_4
 * @property string $beneficiary_name
 * @property string $beneficiary_address_line_1
 * @property string $beneficiary_address_line_2
 * @property string $beneficiary_bank_name
 * @property string $beneficiary_bank_address_1
 * @property string $beneficiary_bank_address_2
 * @property string $beneficiary_bank_address_3
 * @property string $swift
 * @property string $intermediary_account
 * @property string $intermediary_swift
 * @property string $intrmediary_name
 * @property string $intermediary_address_1
 * @property string $intermediary_address_2
 * @property string $intermediary_address_3
 * @property string $charges_type
 * @property string $sort_code
 * @property string $BIC_code
 * @property string $IBAN
 * @property string $ABA_routing_code
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Admin $createdBy
 * @property TransferCandidate $creditNarrative
 * @property Transfer $debitNarrative
 * @property TransferFile $transferFile
 * @property Admin $updatedBy
 */
class TransferFileEntry extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transfer_file_entry';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transfer_file_id', 'debit_narrative', 'credit_narrative', 'created_by', 'updated_by'], 'integer'],
            [['credit_amount', 'exchange_rate'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['tfe_uuid'], 'string', 'max' => 60],
            [['status', 'status_description', 'section_index', 'transfer_method', 'debit_account_no', 'credit_account_no', 'swift', 'intermediary_swift'], 'string', 'max' => 50],
            [['credit_currency'], 'string', 'max' => 3],
            [['dealRefNo', 'value_date', 'payment_details_1', 'payment_details_2', 'payment_details_3', 'payment_details_4', 'beneficiary_name', 'beneficiary_address_line_1', 'beneficiary_address_line_2', 'beneficiary_bank_name', 'beneficiary_bank_address_1', 'beneficiary_bank_address_2', 'beneficiary_bank_address_3', 'intermediary_account', 'intrmediary_name', 'intermediary_address_1', 'intermediary_address_2', 'intermediary_address_3', 'sort_code', 'BIC_code', 'IBAN', 'ABA_routing_code'], 'string', 'max' => 100],
            [['charges_type'], 'string', 'max' => 10],
            [['tfe_uuid'], 'unique'],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['created_by' => 'admin_id']],
            [['credit_narrative'], 'exist', 'skipOnError' => true, 'targetClass' => TransferCandidate::class, 'targetAttribute' => ['credit_narrative' => 'tc_id']],
            [['debit_narrative'], 'exist', 'skipOnError' => true, 'targetClass' => Transfer::class, 'targetAttribute' => ['debit_narrative' => 'transfer_id']],
            [['transfer_file_id'], 'exist', 'skipOnError' => true, 'targetClass' => TransferFile::class, 'targetAttribute' => ['transfer_file_id' => 'transfer_file_id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['updated_by' => 'admin_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'tfe_uuid',
                ],
                'value' => function() {
                    if (!$this->tfe_uuid)
                        $this->tfe_uuid = 'tfe_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->tfe_uuid;
                }
            ],
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by'
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
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
            'tfe_uuid' => Yii::t('app', 'Tfe Uuid'),
            'transfer_file_id' => Yii::t('app', 'Transfer File ID'),
            'status' => Yii::t('app', 'Status'),
            'status_description' => Yii::t('app', 'Status Description'),
            'section_index' => Yii::t('app', 'Section Index'),
            'transfer_method' => Yii::t('app', 'Transfer Method'),
            'credit_amount' => Yii::t('app', 'Credit Amount'),
            'credit_currency' => Yii::t('app', 'Credit Currency'),
            'exchange_rate' => Yii::t('app', 'Exchange Rate'),
            'dealRefNo' => Yii::t('app', 'Deal Ref No'),
            'value_date' => Yii::t('app', 'Value Date'),
            'debit_account_no' => Yii::t('app', 'Debit Account No'),
            'credit_account_no' => Yii::t('app', 'Credit Account No'),
            'debit_narrative' => Yii::t('app', 'Debit Narrative'),
            'credit_narrative' => Yii::t('app', 'Credit Narrative'),
            'payment_details_1' => Yii::t('app', 'Payment Details 1'),
            'payment_details_2' => Yii::t('app', 'Payment Details 2'),
            'payment_details_3' => Yii::t('app', 'Payment Details 3'),
            'payment_details_4' => Yii::t('app', 'Payment Details 4'),
            'beneficiary_name' => Yii::t('app', 'Beneficiary Name'),
            'beneficiary_address_line_1' => Yii::t('app', 'Beneficiary Address Line 1'),
            'beneficiary_address_line_2' => Yii::t('app', 'Beneficiary Address Line 2'),
            'beneficiary_bank_name' => Yii::t('app', 'Beneficiary Bank Name'),
            'beneficiary_bank_address_1' => Yii::t('app', 'Beneficiary Bank Address 1'),
            'beneficiary_bank_address_2' => Yii::t('app', 'Beneficiary Bank Address 2'),
            'beneficiary_bank_address_3' => Yii::t('app', 'Beneficiary Bank Address 3'),
            'swift' => Yii::t('app', 'Swift'),
            'intermediary_account' => Yii::t('app', 'Intermediary Account'),
            'intermediary_swift' => Yii::t('app', 'Intermediary Swift'),
            'intrmediary_name' => Yii::t('app', 'Intrmediary Name'),
            'intermediary_address_1' => Yii::t('app', 'Intermediary Address 1'),
            'intermediary_address_2' => Yii::t('app', 'Intermediary Address 2'),
            'intermediary_address_3' => Yii::t('app', 'Intermediary Address 3'),
            'charges_type' => Yii::t('app', 'Charges Type'),
            'sort_code' => Yii::t('app', 'Sort Code'),
            'BIC_code' => Yii::t('app', 'Bic Code'),
            'IBAN' => Yii::t('app', 'Iban'),
            'ABA_routing_code' => Yii::t('app', 'Aba Routing Code'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreditNarrative($modelClass = "\common\models\TransferCandidate")
    {
        return $this->hasOne($modelClass::className(), ['tc_id' => 'credit_narrative']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDebitNarrative($modelClass = "\common\models\Transfer")
    {
        return $this->hasOne($modelClass::className(), ['transfer_id' => 'debit_narrative']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferFile($modelClass = "\common\models\TransferFile")
    {
        return $this->hasOne($modelClass::className(), ['transfer_file_id' => 'transfer_file_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\common\models\Admin")
    {
        return $this->hasOne($modelClass::className(), ['admin_id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\common\models\Admin")
    {
        return $this->hasOne($modelClass::className(), ['admin_id' => 'updated_by']);
    }
}
