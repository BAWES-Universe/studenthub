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

    /**
     * @param string $className
     * @return \yii\db\ActiveQuery
     */
    public function getStore($className = '\candidate\models\Store') {
        return parent::getStore($className);
    }

    /**
     * @param string $className
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($className = '\candidate\models\Company') {
        return parent::getCompany($className);
    }
}
