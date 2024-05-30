<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "store_assignment_request".
 *
 * @property string $sar_uuid
 * @property int $candidate_id
 * @property int $store_id
 * @property string $currency_code
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 * @property Store $store
 */
class StoreAssignmentRequest extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_CANCELLED = 3;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'store_assignment_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['candidate_id'], 'required'],
            [['candidate_id', 'store_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['sar_uuid'], 'string', 'max' => 60],
            [['currency_code'], 'string', 'max' => 3],
            [['sar_uuid'], 'unique'],
            [['candidate_id'], 'exist', 'skipOnError' => true, 'targetClass' => Candidate::className(), 'targetAttribute' => ['candidate_id' => 'candidate_id']],
            [['store_id'], 'exist', 'skipOnError' => true, 'targetClass' => Store::className(), 'targetAttribute' => ['store_id' => 'store_id']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors()
    {
        return [
            [
                'class' => AttributeBehavior::className(),
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'sar_uuid',
                ],
                'value' => function () {
                    if (!$this->sar_uuid)
                        $this->sar_uuid = 'sar_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->sar_uuid;
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
            'sar_uuid' => Yii::t('app', 'Sar Uuid'),
            'candidate_id' => Yii::t('app', 'Candidate ID'),
            'store_id' => Yii::t('app', 'Store ID'),
            'currency_code' => Yii::t('app', 'currency_code'),
            'status' => Yii::t('app', 'Status'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @param $insert
     * @param $changedAttributes
     * @return bool
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        //todo: notification on accept/ reject

        return true;
    }

    /**
     * @return array|false|int[]|string[]
     */
    public function extraFields()
    {
        return array_merge(['candidate', 'store'], parent::extraFields());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\common\models\Store")
    {
        return $this->hasOne($modelClass::className(), ['store_id' => 'store_id']);
    }
}
