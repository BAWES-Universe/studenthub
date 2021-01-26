<?php
namespace company\models;


/**
 * This is the model class for table "ContactEmail".
 * It extends from \common\models\ContactEmail but with custom functionality for this application module
 */
class ContactEmail extends \common\models\ContactEmail
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContact($modelClass = "\company\models\Contact")
    {
        return parent::getContact($modelClass);
    }
}
