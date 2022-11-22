<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "bank".
 *
 * @property string $bank_uuid
 * @property string|null $bank_name
 * @property string|null $bank_iban_code
 * @property string|null $bank_swift_code
 * @property string|null $bank_address
 * @property string|null $bank_transfer_type
 * @property int|null $deleted
 *
 * @property Transfer[] $transfers
 */
class WalletBank extends \yii\db\ActiveRecord
{
    const LCL = 'Local Bank Transfer';
    const SWF = 'International Transfer';
    const TRF = 'Within Bank Transfer';

    public static function getDb()
    {
        return \Yii::$app->walletDb;
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'bank';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['deleted'], 'integer'],
            [['bank_uuid'], 'string', 'max' => 60],
            [['bank_name', 'bank_iban_code', 'bank_swift_code'], 'string', 'max' => 100],
            [['bank_address'], 'string', 'max' => 255],
            [['bank_transfer_type'], 'string', 'max' => 3],
            ['bank_transfer_type', 'in', 'range' => self::getBankCodeList()]
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'bank_uuid',
                ],
                'value' => function() {
                    if (!$this->bank_uuid)
                        $this->bank_uuid = 'bank_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->bank_uuid;
                }
            ],
        ];
    }

    /**
     * @return array
     */
    private static function getBankCodeList() {
        return ['LCL','SWF','TRF'];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'bank_uuid' => Yii::t('app', 'Bank Uuid'),
            'bank_name' => Yii::t('app', 'Bank Name'),
            'bank_iban_code' => Yii::t('app', 'Bank Iban Code'),
            'bank_swift_code' => Yii::t('app', 'Bank Swift Code'),
            'bank_address' => Yii::t('app', 'Bank Address'),
            'bank_transfer_type' => Yii::t('app', 'Bank Transfer Type'),
            'deleted' => Yii::t('app', 'Deleted'),
        ];
    }

    /**
     * Gets query for [[Transfers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTransfers($modelClass = "\common\models\WalletTransfer")
    {
        return $this->hasMany($modelClass::className(), ['bank_uuid' => 'bank_uuid']);
    }
}
