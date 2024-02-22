<?php

namespace admin\models;


class Suggestion extends \common\models\Suggestion
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\admin\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\common\models\Fulltimer")
    {
        return parent::getFulltimer($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNote($modelClass = "\admin\models\Note")
    {
        return parent::getNote($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\common\models\Request")
    {
        return parent::getRequest($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\admin\models\Staff")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\admin\models\Staff")
    {
        return parent::getUpdatedBy($modelClass);
    }

    /**
     * Show latest feedback in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedback($modelClass = "\admin\models\Note")
    {
        return parent::getFeedback($modelClass);
    }

    /**
     * Show feedbacks in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbacks($modelClass = "\admin\models\Note")
    {
        return parent::getFeedbacks($modelClass);
    }
}