<?php

namespace candidate\models;


class Brand extends \common\models\Brand
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\candidate\models\Store")
    {
        return parent::getStores($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\candidate\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }
}