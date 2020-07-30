<?php
namespace candidate\models;

use Yii;

class CandidateWorkHistory extends \common\models\CandidateWorkHistory {

    /**
     * @return array
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        return $fields;
    }


    public function getStore($className = '\candidate\models\Store') {
        return parent::getStore($className);
    }
}
