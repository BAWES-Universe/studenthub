<?php

namespace admin\models;


class Invitation extends \common\models\Invitation
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\admin\models\Suggestion")
    {
        return parent::getSuggestion($modelClass);
    }

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
    public function getInvitationCreatedByCompany($modelClass = "\admin\models\Company")
    {
        return parent::getInvitationCreatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByStaff($modelClass = "\admin\models\Staff")
    {
        return parent::getInvitationCreatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByCompany($modelClass = "\admin\models\Company")
    {
        return parent::getInvitationUpdatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByStaff($modelClass = "\admin\models\Staff")
    {
        return parent::getInvitationUpdatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\admin\models\Request")
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
    public function getNotes($modelClass = "\admin\models\Note")
    {
        return parent::getNotes($modelClass);
    }
}
