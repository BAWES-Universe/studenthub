<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "staff".
 *
 * @property integer $staff_id
 * @property string $staff_name
 * @property string $staff_email
 * @property string $staff_auth_key
 * @property string $staff_password_hash
 * @property string $staff_password_reset_token
 * @property integer $staff_status
 * @property integer $staff_created_at
 * @property integer $staff_updated_at
 */
class Staff extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'staff';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['staff_name', 'staff_email', 'staff_auth_key', 'staff_password_hash', 'staff_created_at', 'staff_updated_at'], 'required'],
            [['staff_status', 'staff_created_at', 'staff_updated_at'], 'integer'],
            [['staff_name', 'staff_email', 'staff_password_hash', 'staff_password_reset_token'], 'string', 'max' => 255],
            [['staff_auth_key'], 'string', 'max' => 32],
            [['staff_email'], 'unique'],
            [['staff_password_reset_token'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'staff_id' => 'Staff ID',
            'staff_name' => 'Staff Name',
            'staff_email' => 'Staff Email',
            'staff_auth_key' => 'Staff Auth Key',
            'staff_password_hash' => 'Staff Password Hash',
            'staff_password_reset_token' => 'Staff Password Reset Token',
            'staff_status' => 'Staff Status',
            'staff_created_at' => 'Staff Created At',
            'staff_updated_at' => 'Staff Updated At',
        ];
    }
}
