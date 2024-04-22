<?php

namespace manager\models;

class Mall extends \common\models\Mall
{
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
