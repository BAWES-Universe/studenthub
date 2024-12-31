<?php

namespace candidate\models;

use Yii;

class Job extends \common\models\Job
{
    public function extraFields()
    {
        return array_merge(['jobInterest'], parent::extraFields());
    }

    /**
     * @param $modelClass
     */
    public function getJobInterest($modelClass = "\common\models\JobInterest")
    {
        return parent::getJobInterests($modelClass)
            ->andWhere(['candidate_id' => Yii::$app->user->getId()])
            ->one();
    }
}