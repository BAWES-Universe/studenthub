<?php

namespace admin\models;


class CandidateWorkHistory extends \common\models\CandidateWorkHistory
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($className = '\admin\models\Company') {
        return parent::getCompany ($className);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($className = '\admin\models\Store') {
        return parent::getStore($className);
    }
}
