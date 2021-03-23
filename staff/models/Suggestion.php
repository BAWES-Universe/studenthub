<?php

namespace staff\models;

class Suggestion extends \common\models\Suggestion
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return parent::fields();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\staff\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNote($modelClass = "\staff\models\Note")
    {
        return parent::getNote($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\staff\models\Request")
    {
        return parent::getRequest($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getCreatedBy($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\staff\models\Staff")
    {
        return parent::getUpdatedBy($modelClass);
    }

    /**
     * Show latest feedback in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedback($modelClass = "\staff\models\Note")
    {
        return parent::getFeedback($modelClass);
    }

    /**
     * Show feedbacks in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbacks($modelClass = "\staff\models\Note")
    {
        return parent::getFeedbacks($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\staff\models\Fulltimer")
    {
        return parent::getFulltimer($modelClass);
    }
}
