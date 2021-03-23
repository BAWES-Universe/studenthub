<?php


namespace staff\models;


class CandidateWorkHistory extends \common\models\CandidateWorkHistory
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($className = '\staff\models\Company') {
        return parent::getCompany ($className);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($className = '\staff\models\Store') {
        return parent::getStore($className);
    }
}
