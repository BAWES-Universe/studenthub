<?php

namespace common\models;

use Segment\Segment;
use Yii;
use yii\behaviors\AttributeBehavior;


/**
 * This is the model class for table "balance_account".
 *
 * @property string $balance_account_uuid
 * @property string $account_uuid
 * @property string $type invoice, payment, providerf, user
 * @property float $balance
 */
class BalanceAccount extends \yii\db\ActiveRecord
{    
    const TYPE_USER_PAYABLE = "Payable_for_this_user_uuid";
    const TYPE_PAYABLE_TO_USERS = "PayableToUsers";

    public static function getDb()
    {
        return \Yii::$app->walletDb;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'balance_account';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['account_uuid', 'type'], 'required'],
            [['balance'], 'number'],
            [['balance_account_uuid', 'account_uuid', 'type'], 'string', 'max' => 60],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'balance_account_uuid',
                ],
                'value' => function() {
                    if (!$this->balance_account_uuid)
                        $this->balance_account_uuid = 'balance_account_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->balance_account_uuid;
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
            'balance_account_uuid' => Yii::t('app', 'ID'),
            'account_uuid' => Yii::t('app', 'Account Uuid'),
            'type' => Yii::t('app', 'Type'),
            'balance' => Yii::t('app', 'Balance'),
        ];
    }
    
    /**
     * return balance for given account
     * @param type $account_uuid
     * @param type $type
     * @return type
     */
    public static function getBalance($account_uuid, $type) 
    {
        $row = self::findOne([
                'type' => $type,
                'account_uuid' => $account_uuid
            ]);

        return $row? $row->balance: 0;
    }
    
    /**
     * Gets query for [[BalanceTransaction]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBalanceTransactions($modelClass = '\common\models\BalanceTransaction')
    {
        return $this->hasMany($modelClass::className(), ['account_uuid' => 'balance_account_uuid']);
    }

    /**
     * add wallet entry
     * @param $model
     * @param $balanceTransaction
     * @return void
     */
    public static function addEntry($model, $amount, $description, $transaction_datetime = null, $file = null)
    {
        $data = [
            'user_uuid' => $model->user_uuid,
            'data' => $description,
            'file' => $file
        ];

        BalanceAccount::balanceSheet(
            $model->user_uuid,
            BalanceAccount::TYPE_USER_PAYABLE,
            $amount,
            $data,
            $transaction_datetime
        );

        BalanceAccount::balanceSheet(
            $model->user_uuid,
            BalanceAccount::TYPE_PAYABLE_TO_USERS,
            $amount,
            $data,
            $transaction_datetime
        );

        if(YII_ENV == 'prod')
        {

            $segmentStatus = Yii::$app->config->get('Segment-Status');

            $segmentKey = Yii::$app->config->get('Segment-Key');
            $walletSegmentKey = Yii::$app->config->get('Segment-Key-Wallet');

            if($segmentStatus) {
                Yii::$app->eventManager->initSegment($walletSegmentKey);
            }

            if (!Yii::$app->user->isGuest)
            {
                $user = Yii::$app->user->identity;

                Yii::$app->eventManager->setUser(
                    Yii::$app->user->getId (), [
                        "name" => $user->username,
                        "email" => $user->email
                    ]);
            }

            Yii::$app->eventManager->track (
                'New Wallet Entry', [
                    'user_uuid' => $model->user_uuid,
                    'name' => $model->username,
                    'email' => $model->email,
                    'amount' => $amount,
                    'currency' => 'KWD',
                    'revenue' => $amount,//just for beautiful graphs
                ],
                $transaction_datetime
            );

            if($segmentStatus) {
                Yii::$app->eventManager->initSegment($segmentKey);
            }
        }
    }

    /**
     * pay by wallet amount
     * @param $model
     * @param $balanceTransaction
     * @return void
     */
    public static function payByWallet($model, $amount, $to, $transaction_datetime = null)
    {
        $attributes1 = [
            'user_uuid' => $model->user_uuid,
            'data' => "Paid to " .  $to->username,
        ];

        BalanceAccount::balanceSheet(
            $model->user_uuid,
            BalanceAccount::TYPE_USER_PAYABLE,
            0 - $amount,
            $attributes1,
            $transaction_datetime
        );

        $attributes2 = [
            'user_uuid' => $to->user_uuid,
            'data' => "Received from " .  $model->username,
        ];

        BalanceAccount::balanceSheet(
            $to->user_uuid,
            BalanceAccount::TYPE_USER_PAYABLE,
            $amount,
            $attributes2,
            $transaction_datetime
        );

        if(YII_ENV == 'prod')
        {
            $segmentStatus = Yii::$app->config->get('Segment-Status');

            $segmentKey = Yii::$app->config->get('Segment-Key');
            $walletSegmentKey = Yii::$app->config->get('Segment-Key-Wallet');

            if($segmentStatus) {
                Yii::$app->eventManager->initSegment($walletSegmentKey);
            }

            if (!Yii::$app->user->isGuest)
            {
                $user = Yii::$app->user->identity;

                Yii::$app->eventManager->setUser(Yii::$app->user->getId(), [
                        "name" => $user->username,
                        "email" => $user->email
                    ]);
            }

            Yii::$app->eventManager->track ('Paid By Wallet', [
                    'user_uuid' => $model->user_uuid,
                    'name' => $model->username,
                    'email' => $model->email,
                    'to_uuid' => $to->user_uuid,
                    'to_name' => $to->username,
                    'to_email' => $to->email,
                    'amount' => $amount,
                    'currency' => 'KWD',
                    'revenue' => 0,//just for beautiful graphs - not affect revenue
                ],
                $transaction_datetime
            );
            
            if($segmentStatus) {
                Yii::$app->eventManager->initSegment($segmentKey);
            }

        }
    }

    /*
     * maintain accounting
     */
    public static function balanceSheet($accountId, $type, $amount, $data, $transaction_datetime) {

        //check if wallet created for this account

        $account = BalanceAccount::findOne([
            'account_uuid' => $accountId,
            'type' => $type,
        ]);

        if(!$account) {
            $account = new BalanceAccount;
            $account->balance = 0;
            $account->account_uuid = $accountId;
            $account->type = $type;
            $account->save();
        }

        //extra data for date representation

        $currentBalance = (float) BalanceAccount::getBalance($accountId, $type);

        $attributes = array_merge($data, [
            //effective balance to transaction
            'balance' => $amount + $currentBalance,
            'balance_transaction_uuid' => 'balance_transaction_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar(),
            'transaction_datetime' => $transaction_datetime? date('Y-m-d H:i:s', strtotime($transaction_datetime)): date('Y-m-d H:i:s')
        ]);

        Yii::$app->balanceManager->increase(
            [
                'account_uuid' => $accountId,
                'type' => $type
            ],
            $amount,
            $attributes
        );

        return $amount + $currentBalance;
    }
}
