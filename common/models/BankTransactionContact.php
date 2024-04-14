<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "bank_transaction_contact".
 *
 * @property string $contact_id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 *
 * @property BankTransaction[] $bankTransactions
 */
class BankTransactionContact extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'bank_transaction_contact';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contact_id'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['contact_id'], 'string', 'max' => 60],
            [['name'], 'string', 'max' => 255],
            [['contact_id'], 'unique'],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'contact_id',
                ],
                'value' => function () {
                    if (!$this->contact_id)
                        $this->contact_id = 'contact_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->contact_id;
                }
            ],
            [
                'class' => TimestampBehavior::className(),
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
            'contact_id' => Yii::t('app', 'Contact ID'),
            'name' => Yii::t('app', 'Name'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getBankTransactions($modelClass = "\common\models\BankTransaction")
    {
        return $this->hasMany($modelClass::className(), ['contact_id' => 'contact_id']);
    }
}
