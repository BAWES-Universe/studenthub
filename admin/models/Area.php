<?php

namespace admin\models;


class Area extends \common\models\Area
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\admin\models\Country")
    {
        return parent::getCountry($modelClass);
    }
}