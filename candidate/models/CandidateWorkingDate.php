<?php

namespace candidate\models;

class CandidateWorkingDate extends  \common\models\CandidateWorkingDate
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\candidate\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        returnparent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStore($modelClass = "\candidate\models\Store")
    {
        return parent::getStore($modelClass);
    }
}
