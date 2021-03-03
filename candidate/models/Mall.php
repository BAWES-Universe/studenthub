<?php

namespace candidate\models;


class Mall extends \common\models\Mall
{
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