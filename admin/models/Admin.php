<?php
namespace admin\models;

use Yii;

/**
 * This is the model class for table "Admin".
 * It extends from \common\models\Admin but with custom functionality for this application module
 */
class Admin extends \common\models\Admin {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['admin_auth_key'],
        $fields['admin_password_hash'],
        $fields['admin_password_reset_token'],
        $fields['admin_created_at'],
        $fields['admin_updated_at']);

        return $fields;
    }
}
