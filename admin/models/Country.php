<?php


namespace admin\models;


class Country extends \common\models\Country
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidates ($modelClass);
    }
}