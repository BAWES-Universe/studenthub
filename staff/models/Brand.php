<?php


namespace staff\models;


class Brand extends \common\models\CandidateEmailVerifyAttempt
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\staff\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\staff\models\Store")
    {
        return parent::getStores($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }
}