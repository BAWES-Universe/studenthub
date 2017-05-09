<?php
namespace staff\models;

use Yii;

/**
 * This is the model class for table "Staff".
 * It extends from \common\models\Staff but with custom functionality for this application module
 */
class Staff extends \common\models\Staff {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['staff_auth_key'],
        $fields['staff_password_hash'],
        $fields['staff_password_reset_token'],
        $fields['staff_created_at'],
        $fields['staff_updated_at']);

        return $fields;
    }
    
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = StaffToken::find()->where(['token_value' => $token])->with('staff')->one();
        if($token){
            return $token->staff;
        }
    }
}
