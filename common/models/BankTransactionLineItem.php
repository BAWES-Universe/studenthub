<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "bank_transaction_line_item".
 *
 * @property string $line_item_id
 * @property string $bank_transaction_id
 * @property string $account_code
 * @property string $account_id
 * @property string $description
 * @property double $discount_amount
 * @property double $discount_rate
 * @property string $item_code
 * @property double $line_amount
 * @property int $quantity
 * @property string $repeating_invoice_id
 * @property double $tax_amount
 * @property string $tax_type
 * @property double $unit_amount
 *
 * @property BankTransaction $bankTransaction
 */
class BankTransactionLineItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'bank_transaction_line_item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['line_item_id'], 'required'],
            [['discount_amount', 'discount_rate', 'line_amount', 'tax_amount', 'unit_amount'], 'number'],
            [['quantity'], 'integer'],
            [['line_item_id', 'bank_transaction_id'], 'string', 'max' => 60],
            [['account_code', 'account_id', 'description', 'item_code', 'repeating_invoice_id', 'tax_type'], 'string', 'max' => 255],
            [['line_item_id'], 'unique'],
            [['bank_transaction_id'], 'exist', 'skipOnError' => true, 'targetClass' => BankTransaction::className(), 'targetAttribute' => ['bank_transaction_id' => 'bank_transaction_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'line_item_id' => Yii::t('app', 'Line Item ID'),
            'bank_transaction_id' => Yii::t('app', 'Bank Transaction ID'),
            'account_code' => Yii::t('app', 'Account Code'),
            'account_id' => Yii::t('app', 'Account ID'),
            'description' => Yii::t('app', 'Description'),
            'discount_amount' => Yii::t('app', 'Discount Amount'),
            'discount_rate' => Yii::t('app', 'Discount Rate'),
            'item_code' => Yii::t('app', 'Item Code'),
            'line_amount' => Yii::t('app', 'Line Amount'),
            'quantity' => Yii::t('app', 'Quantity'),
            'repeating_invoice_id' => Yii::t('app', 'Repeating Invoice ID'),
            'tax_amount' => Yii::t('app', 'Tax Amount'),
            'tax_type' => Yii::t('app', 'Tax Type'),
            'unit_amount' => Yii::t('app', 'Unit Amount'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBankTransaction($modelClass = "\common\models\BankTransaction")
    {
        return $this->hasOne($modelClass::className(), ['bank_transaction_id' => 'bank_transaction_id']);
    }
}
