<?php
namespace company\models;


/**
 * This is the model class for table "Staff".
 * It extends from \common\models\Staff but with custom functionality for this application module
 */
class Staff extends \common\models\Staff
{
    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        unset(
            $fields['staff_auth_key'],
            $fields['staff_password_hash'],
            $fields['staff_password_reset_token'],
            $fields['deleted']
        );
        // remove fields that contain sensitive information
        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\company\models\Note")
    {
        return parent::getNotes ($modelClass);
    }
}
