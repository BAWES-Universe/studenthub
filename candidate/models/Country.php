<?php
namespace candidate\models;


/**
 * This is the model class for table "Country".
 * It extends from \common\models\Country but with custom functionality for this application module
 */
class Country extends \common\models\Country {

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

