<?php

namespace candidate\models;

class CandidateNotification extends \common\models\CandidateNotification
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkLogFeedback($modelClass = "\common\models\CandidateWorkLogFeedback")
    {
        return parent::getCandidateWorkLogFeedback($modelClass);
    }

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
    public function getCandidateWorkHistory($modelClass = "\candidate\models\CandidateWorkHistory")
    {
        return parent::getCandidateWorkHistory($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidateWorkingDate($modelClass = "\candidate\models\CandidateWorkingDate")
    {
        return parent::getCandidateWorkingDate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitation($modelClass = "\candidate\models\Invitation")
    {
        return parent::getInvitation($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidate($modelClass = "\candidate\models\TransferCandidate")
    {
        return parent::getTransferCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\candidate\models\Request")
    {
        return parent::getRequest($modelClass);
    }
}