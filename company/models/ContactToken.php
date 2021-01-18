<?php
namespace company\models;

/**
 * This is the model class for table "ContactToken".
 * It extends from \common\models\ContactToken but with custom functionality for this application module
 *
 */
class ContactToken extends \common\models\ContactToken {

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\company\models\Contact")
    {
        return parent::getContact($modelClass);
    }
}