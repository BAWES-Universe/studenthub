<?php


namespace admin\models;


class University extends \common\models\University
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidates($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidates ($modelClass);
    }
}