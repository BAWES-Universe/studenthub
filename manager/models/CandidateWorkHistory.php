<?php

namespace manager\models;


class CandidateWorkHistory extends \common\models\CandidateWorkHistory
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($className = '\manager\models\Company') {
        return parent::getCompany ($className);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($className = '\manager\models\Store') {
        return parent::getStore($className);
    }
}
