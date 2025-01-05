<?php

namespace common\models;

use Yii;
use yii\behaviors\AttributeBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "company_stats".
 *
 * @property string $cs_uuid
 * @property int $company_id
 * @property string $total_revenue
 * @property string $currency_code
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Company $company
 */
class CompanyStats extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'company_stats';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['total_revenue', 'currency_code'], 'required'],//'cs_uuid',
            [['company_id'], 'integer'],
            [['total_revenue'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['cs_uuid'], 'string', 'max' => 60],
            [['currency_code'], 'string', 'max' => 3],
            [['cs_uuid'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    /**
     * @return array[]
     */
    public function behaviors() {
        return [
            [
                'class' => AttributeBehavior::class,
                'attributes' => [
                    \yii\db\ActiveRecord::EVENT_BEFORE_INSERT => 'cs_uuid',
                ],
                'value' => function() {
                    if (!$this->cs_uuid)
                        $this->cs_uuid = 'cs_' . Yii::$app->db->createCommand('SELECT uuid()')->queryScalar();

                    return $this->cs_uuid;
                }
            ],
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => "updated_at",
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
            'cs_uuid' => Yii::t('app', 'Cs Uuid'),
            'company_id' => Yii::t('app', 'Company ID'),
            'total_revenue' => Yii::t('app', 'Total Revenue'),
            'currency_code' => Yii::t('app', 'Currency Code'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\common\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'company_id']);
    }

    /**
     * @inheritdoc
     * @return query\CompanyStatsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new query\CompanyStatsQuery(get_called_class());
    }
}
