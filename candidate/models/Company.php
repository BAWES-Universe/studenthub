<?php
namespace candidate\models;

use Yii;
/**
 * This is the model class for table "Company".
 * It extends from \common\models\Company but with custom functionality for this application module
 */
class Company extends \common\models\Company {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['company_hourly_rate'],
            $fields['company_bonus_commission'],
            $fields['company_status']);

        return $fields;
    }
}

