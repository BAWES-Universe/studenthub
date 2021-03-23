<?php

namespace company\models;


class CandidateWorkHistory extends \common\models\CandidateWorkHistory
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($className = '\company\models\Company') {
        return parent::getCompany ($className);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($className = '\company\models\Store') {
        return parent::getStore($className);
    }
}
