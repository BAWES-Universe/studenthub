<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "bank_transaction".
 *
 * @property string $bank_transaction_id
 * @property string $contact_id
 * @property double $currency_rate
 * @property string $currency_code
 * @property int $has_attachments
 * @property int $is_reconciled
 * @property string $line_amount_types
 * @property string $overpayment_id
 * @property string $prepayment_id
 * @property string $reference
 * @property string $status
 * @property string $status_attribute_string
 * @property double $sub_total
 * @property double $total
 * @property double $total_tax
 * @property string $type
 * @property string $url
 * @property string $validation_errors
 * @property string $date
 * @property string $created_at
 * @property string $updated_at
 *
 *
 * @property BankTransactionContact $contact
 * @property BankTransactionLineItem[] $bankTransactionLineItems
 */
class BankTransaction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'bank_transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['bank_transaction_id'], 'required'],
            [['currency_rate', 'sub_total', 'total', 'total_tax'], 'number'],
            //[['has_attachments', 'is_reconciled'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['bank_transaction_id', 'contact_id'], 'string', 'max' => 60],
            [['currency_code'], 'string', 'max' => 3],
            [['line_amount_types'], 'string', 'max' => 100],
            [['overpayment_id', 'prepayment_id', 'reference', 'status', 'status_attribute_string', 'type', 'url', 'validation_errors'], 'string', 'max' => 255],
            [['bank_transaction_id'], 'unique'],
            [['contact_id'], 'exist', 'skipOnError' => true, 'targetClass' => BankTransactionContact::class, 'targetAttribute' => ['contact_id' => 'contact_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'bank_transaction_id',
                ],
                'value' => function () {
                    if (!$this->bank_transaction_id)
                        $this->bank_transaction_id = 'bank_transaction_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->bank_transaction_id;
                }
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
            'bank_transaction_id' => Yii::t('app', 'Bank Transaction ID'),
            'contact_id' => Yii::t('app', 'Contact ID'),
            'currency_rate' => Yii::t('app', 'Currency Rate'),
            'currency_code' => Yii::t('app', 'Currency Code'),
            'has_attachments' => Yii::t('app', 'Has Attachments'),
            'is_reconciled' => Yii::t('app', 'Is Reconciled'),
            'line_amount_types' => Yii::t('app', 'Line Amount Types'),
            'overpayment_id' => Yii::t('app', 'Overpayment ID'),
            'prepayment_id' => Yii::t('app', 'Prepayment ID'),
            'reference' => Yii::t('app', 'Reference'),
            'status' => Yii::t('app', 'Status'),
            'status_attribute_string' => Yii::t('app', 'Status Attribute String'),
            'sub_total' => Yii::t('app', 'Sub Total'),
            'total' => Yii::t('app', 'Total'),
            'total_tax' => Yii::t('app', 'Total Tax'),
            'type' => Yii::t('app', 'Type'),
            'url' => Yii::t('app', 'Url'),
            'validation_errors' => Yii::t('app', 'Validation Errors'),
            'date' => Yii::t('app', 'Date'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'contact',
            'bankTransactionLineItems'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\common\models\BankTransactionContact")
    {
        return $this->hasOne($modelClass::className(), ['contact_id' => 'contact_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBankTransactionLineItems($modelClass = "\common\models\BankTransactionLineItem")
    {
        return $this->hasMany($modelClass::className(), ['bank_transaction_id' => 'bank_transaction_id']);
    }
}
