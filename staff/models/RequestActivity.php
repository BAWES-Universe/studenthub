<?php

namespace staff\models;


/**
 * This is the model class for table "Request".
 * It extends from \common\models\Request but with custom functionality for this application module
 */
class RequestActivity extends \common\models\RequestActivity {

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
//        unset($fields['deleted']);
        return $fields;
    }

    public function getStaff($modelName = '\staff\models\Staff')
    {
        return parent::getStaff($modelName);
    }
}
