<?php


namespace manager\models;


class Brand extends \common\models\Brand
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\manager\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\manager\models\Store")
    {
        return parent::getStores($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\manager\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }
}