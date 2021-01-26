<?php
namespace company\models;


/**
 * This is the model class for table "ContactPhone".
 * It extends from \common\models\ContactPhone but with custom functionality for this application module
 */
class ContactPhone extends \common\models\ContactPhone
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\company\models\Contact")
    {
        return parent::getContact($modelClass);
    }
}
