<?php
namespace manager\models;


/**
 * This is the model class for table "Suggestion".
 * It extends from \common\models\Suggestion but with custom functionality for this application module
 */
class Suggestion extends \common\models\Suggestion
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\manager\models\Candidate")
    {
        return parent::getCandidate ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFulltimer($modelClass = "\manager\models\Fulltimer")
    {
        return parent::getFulltimer($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNote($modelClass = "\manager\models\Note")
    {
        return parent::getNote ($modelClass)
            ->filterNonInternal();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\manager\models\Request")
    {
        return parent::getRequest ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy($modelClass = "\manager\models\Staff")
    {
        return parent::getCreatedBy ($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy($modelClass = "\manager\models\Staff")
    {
        return parent::getUpdatedBy ($modelClass);
    }

    /**
     * Show latest feedback in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedback($modelClass = "\manager\models\Note")
    {
        return parent::getFeedback($modelClass)
            ->filterNonInternal();
    }

    /**
     * Show feedbacks in suggestion
     * @return \yii\db\ActiveQuery
     */
    public function getFeedbacks($modelClass = "\manager\models\Note")
    {
        return parent::getFeedbacks($modelClass)
            ->filterNonInternal();
    }
}
