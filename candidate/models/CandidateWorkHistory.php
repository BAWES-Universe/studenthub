<?php
namespace candidate\models;

use Yii;

class CandidateWorkHistory extends \common\models\CandidateWorkHistory {

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
