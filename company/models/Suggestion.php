<?php
namespace company\models;


/**
 * This is the model class for table "Suggestion".
 * It extends from \common\models\Suggestion but with custom functionality for this application module
 */
class Suggestion extends \common\models\Suggestion
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\company\models\Candidate")
    {
        return parent::getCandidate ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\company\models\Fulltimer")
    {
        return parent::getFulltimer($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNote($modelClass = "\company\models\Note")
    {
        return parent::getNote ($modelClass)
            ->filterNonInternal();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\company\models\Request")
    {
        return parent::getRequest ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\company\models\Staff")
    {
        return parent::getCreatedBy ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\company\models\Staff")
    {
        return parent::getUpdatedBy ($modelClass);
    }

    /**
     * Show latest feedback in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedback($modelClass = "\company\models\Note")
    {
        return parent::getFeedback($modelClass)
            ->filterNonInternal();
    }

    /**
     * Show feedbacks in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbacks($modelClass = "\company\models\Note")
    {
        return parent::getFeedbacks($modelClass)
            ->filterNonInternal();
    }
}
