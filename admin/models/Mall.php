<?php

namespace admin\models;


class Mall extends \common\models\Mall
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStores($modelClass = "\admin\models\Store")
    {
        return parent::getStores($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }
}
