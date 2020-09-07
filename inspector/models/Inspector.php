<?php
namespace inspector\models;

use common\models\InspectorToken;
use Yii;

/**
 * This is the model class for table "Inspector".
 * It extends from \common\models\Inspector but with custom functionality for this application module
 */
class Inspector extends \common\models\Inspector {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['inspector_auth_key'],
        $fields['inspector_password_hash'],
        $fields['inspector_password_reset_token'],
        $fields['inspector_created_at'],
        $fields['inspector_updated_at']);

        return $fields;
    }

    /**
     * @param mixed $token
     * @param null $type
     * @return mixed
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = InspectorToken::find()->where(['token_value' => $token])->with('inspector')->one();
        if($token){
            return $token->inspector;
        }
    }
}
