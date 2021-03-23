<?php


namespace staff\models;


class Area extends \common\models\Area
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidates($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCountry($modelClass = "\staff\models\Country")
    {
        return parent::getCountry($modelClass);
    }
}


