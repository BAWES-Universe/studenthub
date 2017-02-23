<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "store".
 *
 * @property integer $store_id
 * @property integer $company_id
 * @property string $store_name
 * @property integer $store_status
 * @property string $store_created_at
 * @property string $store_updated_at
 *
 * @property Company $company
 */
class Store extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'store';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'store_status'], 'integer'],
            [['store_name', 'store_created_at', 'store_updated_at'], 'required'],
            [['store_created_at', 'store_updated_at'], 'safe'],
            [['store_name'], 'string', 'max' => 255],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_id' => 'Store ID',
            'company_id' => 'Company ID',
            'store_name' => 'Store Name',
            'store_status' => 'Store Status',
            'store_created_at' => 'Store Created At',
            'store_updated_at' => 'Store Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }
}
