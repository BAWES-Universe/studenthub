<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "candidate".
 *
 * @property integer $candidate_id
 * @property integer $company_id
 * @property string $candidate_name
 * @property string $candidate_email
 * @property string $candidate_civil_id
 * @property string $candidate_auth_key
 * @property string $candidate_password_hash
 * @property string $candidate_password_reset_token
 * @property integer $candidate_status
 * @property integer $candidate_created_at
 * @property integer $candidate_updated_at
 *
 * @property Company $company
 */
class Candidate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'candidate';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['company_id', 'candidate_status', 'candidate_created_at', 'candidate_updated_at'], 'integer'],
            [['candidate_name', 'candidate_email', 'candidate_civil_id', 'candidate_auth_key', 'candidate_password_hash', 'candidate_created_at', 'candidate_updated_at'], 'required'],
            [['candidate_name', 'candidate_email', 'candidate_civil_id', 'candidate_password_hash', 'candidate_password_reset_token'], 'string', 'max' => 255],
            [['candidate_auth_key'], 'string', 'max' => 32],
            [['candidate_email'], 'unique'],
            [['candidate_civil_id'], 'unique'],
            [['candidate_password_reset_token'], 'unique'],
            [['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::className(), 'targetAttribute' => ['company_id' => 'company_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'candidate_id' => 'Candidate ID',
            'company_id' => 'Company ID',
            'candidate_name' => 'Candidate Name',
            'candidate_email' => 'Candidate Email',
            'candidate_civil_id' => 'Candidate Civil ID',
            'candidate_auth_key' => 'Candidate Auth Key',
            'candidate_password_hash' => 'Candidate Password Hash',
            'candidate_password_reset_token' => 'Candidate Password Reset Token',
            'candidate_status' => 'Candidate Status',
            'candidate_created_at' => 'Candidate Created At',
            'candidate_updated_at' => 'Candidate Updated At',
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
