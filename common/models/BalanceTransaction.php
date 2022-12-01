<?php

namespace common\models;

use Segment\Segment;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\db\Expression;


/**
 * This is the model class for table "balance_transaction".
 *
 * @property string $balance_transaction_uuid
 * @property string $account_uuid
 * @property float $amount
 * @property string|null $user_uuid
 * @property float $balance
 * @property string|null $data
 * @property string|null $file
 * @property string|null $transaction_datetime
 * @property string $created_at
 *
 * @property User $user
 */
class BalanceTransaction extends \yii\db\ActiveRecord
{
    public $type;

    public static function getDb()
    {
        return \Yii::$app->walletDb;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'balance_transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account_uuid', 'created_at'], 'required'],
            [['amount', 'balance','type'], 'number'],
            [['data'], 'string'],
            [['created_at', 'transaction_datetime','file'], 'safe'],
            [['account_uuid', 'user_uuid'], 'string', 'max' => 60],
            [['user_uuid'], 'exist', 'skipOnError' => true, 'targetClass' => WalletUser::className(), 'targetAttribute' => ['user_uuid' => 'user_uuid']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'balance_transaction_uuid',
                ],
                'value' => function() {
                    if (!$this->balance_transaction_uuid)
                        $this->balance_transaction_uuid = 'balance_transaction_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->balance_transaction_uuid;
                }
            ],
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'transaction_datetime',
                ],
                'value' => function() {
                    if (!$this->transaction_datetime)
                        $this->transaction_datetime = new Expression('NOW()');

                    return $this->transaction_datetime;
                }
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'balance_transaction_uuid' => Yii::t('app', 'ID'),
            'account_uuid' => Yii::t('app', 'Account Uuid'),
            'amount' => Yii::t('app', 'Amount'),
            'user_uuid' => Yii::t('app', 'User Uuid'),
            'type' => Yii::t('app', 'Type'),
            'balance' => Yii::t('app', 'Balance'),
            'data' => Yii::t('app', 'Description'),
            'file' => Yii::t('app', 'File'),
            'transaction_datetime' => Yii::t('app', 'Transaction datetime'),
            'created_at' => Yii::t('app', 'Created At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
            'user',
            'balanceAccount'
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser($modelClass = '\common\models\WalletUser')
    {
        return $this->hasOne($modelClass::className(), ['user_uuid' => 'user_uuid']);
    }
    
    /**
     * Gets query for [[BalanceAccount]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBalanceAccount($modelClass = '\common\models\BalanceAccount')
    {
        return $this->hasOne($modelClass::className(), ['balance_account_uuid' => 'account_uuid']);
    }
}
