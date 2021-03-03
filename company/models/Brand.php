<?php


namespace company\models;


class Brand extends \common\models\CandidateEmailVerifyAttempt
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\company\models\Store")
    {
        return parent::getStores($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\company\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }
}