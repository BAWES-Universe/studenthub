<?php

namespace company\models;

use Yii;


class Invitation extends \common\models\Invitation
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\company\models\Note")
    {
        return parent::getNotes($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\company\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByCompany($modelClass = "\company\models\Company")
    {
        return parent::getInvitationCreatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByStaff($modelClass = "\company\models\Staff")
    {
        return parent::getInvitationCreatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByCompany($modelClass = "\company\models\Company")
    {
        return parent::getInvitationUpdatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByStaff($modelClass = "\company\models\Staff")
    {
        return parent::getInvitationUpdatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\company\models\Request")
    {
        return parent::getRequest($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\company\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\company\models\Suggestion")
    {
        return parent::getSuggestion($modelClass);
    }
}