<?php
namespace candidate\models;

use Yii;

class Store extends \common\models\Store {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        unset(
            $fields['store_total_candidates'],
            $fields['store_status'],
            $fields['store_created_at'],
            $fields['company_id'],
            $fields['store_updated_at']
        );
        // remove fields that contain sensitive information
        return $fields;
    }

}
