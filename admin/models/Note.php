<?php
namespace admin\models;

use Yii;

/**
 * This is the model class for table "Note".
 * It extends from \common\models\Note but with custom functionality for this application module
 */
class Note extends \common\models\Note {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\admin\models\Staff")
    {
        return parent::getcreatedBy($modelClass);
    }
}
