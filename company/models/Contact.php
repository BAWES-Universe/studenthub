<?php
namespace company\models;
use common\models\ContactToken;

/**
 * This is the model class for table "Contact".
 * It extends from \common\models\Contact but with custom functionality for this application module
 */
class Contact extends \common\models\Contact {

    /**
     * @return array
     */
    public function fields()
    {
       return parent::fields();
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $token = ContactToken::find()->where(['token_value' => $token])->with('contact')->one();
        if($token){
            return $token->contact;
        }
    }
}
