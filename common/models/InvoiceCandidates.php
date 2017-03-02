<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "invoice_candidates".
 *
 * @property integer $ic_id
 * @property integer $invoice_id
 * @property integer $candidate_id
 * @property string $hours
 * @property string $hourly_rate
 * @property string $bonus
 * @property string $transfer_cost 
 * @property string $ic_created_at
 * @property string $ic_updated_at
 *
 * @property Candidate $candidate
 * @property Invoice $invoice
 */
class InvoiceCandidates extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'invoice_candidates';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['invoice_id', 'candidate_id'], 'integer'],
            [['hours', 'transfer_cost', 'bonus', 'hourly_rate'], 'number'],
            [['ic_created_at', 'ic_updated_at'], 'safe'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::className(), 'targetAttribute' => ['invoice_id' => 'invoice_id']],
        ];
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'ic_created_at',
                'updatedAtAttribute' => 'ic_updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ic_id' => 'Tc ID',
            'invoice_id' => 'Invoice ID',
            'candidate_id' => 'Candidate ID',
            'hours' => 'Hours',
            'hourly_rate' => 'Hourly Rate',
            'transfer_cost' => 'Transfer cost',
            'bonus' => 'Bonus',
            'ic_created_at' => 'Tc Created At',
            'ic_updated_at' => 'Tc Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate()
    {
        return $this->hasOne(Candidate::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::className(), ['invoice_id' => 'invoice_id']);
    }

    public function getTotal()
    {
        return $this->bonus + ($this->hourly_rate * $this->hours) + Yii::$app->params['transfer_cost'];
    }
}
