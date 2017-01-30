<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "company".
 *
 * @property integer $company_id
 * @property string $company_name
 * @property string $company_email
 * @property string $company_auth_key
 * @property string $company_password_hash
 * @property string $company_password_reset_token
 * @property integer $company_status
 * @property integer $company_created_at
 * @property integer $company_updated_at
 *
 * @property Candidate[] $candidates
 */
class Company extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'company';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_name', 'company_email', 'company_auth_key', 'company_password_hash', 'company_created_at', 'company_updated_at'], 'required'],
            [['company_status', 'company_created_at', 'company_updated_at'], 'integer'],
            [['company_name', 'company_email', 'company_password_hash', 'company_password_reset_token'], 'string', 'max' => 255],
            [['company_auth_key'], 'string', 'max' => 32],
            [['company_email'], 'unique'],
            [['company_password_reset_token'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'company_id' => 'Company ID',
            'company_name' => 'Company Name',
            'company_email' => 'Company Email',
            'company_auth_key' => 'Company Auth Key',
            'company_password_hash' => 'Company Password Hash',
            'company_password_reset_token' => 'Company Password Reset Token',
            'company_status' => 'Company Status',
            'company_created_at' => 'Company Created At',
            'company_updated_at' => 'Company Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates()
    {
        return $this->hasMany(Candidate::className(), ['company_id' => 'company_id']);
    }
}
