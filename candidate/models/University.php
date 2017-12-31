<?php
namespace candidate\models;

use Yii;
/**
 * This is the model class for table "University".
 * It extends from \common\models\University but with custom functionality for this application module
 */
class University extends \common\models\University {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['total_candidates']);

        return $fields;
    }
}

