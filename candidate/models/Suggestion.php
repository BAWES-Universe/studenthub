<?php

namespace candidate\models;


class Suggestion extends \common\models\Suggestion
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
    public function getFulltimer($modelClass = "\candidate\models\Fulltimer")
    {
        return parent::getFulltimer($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNote($modelClass = "\candidate\models\Note")
    {
        return parent::getNote($modelClass)
            ->filterNonInternal();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\candidate\models\Request")
    {
        return parent::getRequest($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\candidate\models\Staff")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\candidate\models\Staff")
    {
        return parent::getUpdatedBy($modelClass);
    }

    /**
     * Show latest feedback in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedback($modelClass = "\candidate\models\Note")
    {
        return parent::getFeedback($modelClass)
            ->filterNonInternal();
    }

    /**
     * Show feedbacks in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbacks($modelClass = "\candidate\models\Note")
    {
        return parent::getFeedbacks($modelClass)
            ->filterNonInternal();
    }
}