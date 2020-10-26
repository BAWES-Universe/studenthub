<?php

namespace staff\models;


use common\models\RequestActivity;

/**
 * This is the model class for table "Request".
 * It extends from \common\models\Request but with custom functionality for this application module
 */
class Request extends \common\models\Request {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        return $fields;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestCreatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getRequestCreatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequestUpdatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getRequestUpdatedBy($modelClass);
    }
}
