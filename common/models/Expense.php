<?php

namespace common\models;

use Segment\Segment;
use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "expense".
 *
 * @property string $expense_uuid
 * @property string $title
 * @property string $type
 * @property string $detail
 * @property number $amount
 * @property string $transaction_datetime
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Admin $createdBy
 * @property Admin $updatedBy
 */
class Expense extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'expense';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'type'], 'required'],
            [['detail'], 'string'],
            [['created_by', 'updated_by', 'amount'], 'integer'],
            [['created_at', 'updated_at', 'transaction_datetime'], 'safe'],
            [['expense_uuid'], 'string', 'max' => 60],
            [['title', 'type'], 'string', 'max' => 128],
            [['expense_uuid'], 'unique'],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['created_by' => 'admin_id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Admin::class, 'targetAttribute' => ['updated_by' => 'admin_id']],
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'expense_uuid',
                ],
                'value' => function() {
                    if (!$this->expense_uuid)
                        $this->expense_uuid = 'expense_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->expense_uuid;
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
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave ($insert, $changedAttributes);

        //if(YII_ENV == 'prod') {

            $datetime = $this->transaction_datetime?
                new \DateTime($this->transaction_datetime): new \DateTime($this->created_at);

            Yii::$app->eventManager->track ('Expense Added', [
                    'expense_uuid' => $this->expense_uuid,
                    'title' => $this->title,
                    'type' => $this->type,
                    'detail' => $this->detail,
                    'amount' => $this->amount,
                    'currency' => 'KWD',
                    'revenue' => $this->amount,//just for beautiful graphs
                    'created_by' => $this->createdBy->admin_name
                ],
                $datetime->format('c')
            );
       // }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'expense_uuid' => Yii::t('app', 'Expense Uuid'),
            'title' => Yii::t('app', 'Title'),
            'type' => Yii::t('app', 'Type'),
            'detail' => Yii::t('app', 'Detail'),
            'amount' => Yii::t('app', 'Amount'),
            'transaction_datetime' => Yii::t('app', 'Transaction Datetime'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
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
