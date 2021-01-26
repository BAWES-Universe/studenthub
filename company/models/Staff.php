<?php
namespace company\models;


/**
 * This is the model class for table "Staff".
 * It extends from \common\models\Staff but with custom functionality for this application module
 */
class Staff extends \common\models\Staff
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\company\models\Note")
    {
        return parent::getNotes ($modelClass);
    }
}
