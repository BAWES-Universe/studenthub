<?php

namespace company\models;

class Mall extends \common\models\Mall
{
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
