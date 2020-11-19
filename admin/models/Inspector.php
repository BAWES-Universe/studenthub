<?php
namespace admin\models;

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
        $fields['inspector_password_reset_token']);

        return $fields;
    }
}
