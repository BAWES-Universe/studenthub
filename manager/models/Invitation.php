<?php

namespace manager\models;

use Yii;


class Invitation extends \common\models\Invitation
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\manager\models\Note")
    {
        return parent::getNotes($modelClass)
            ->filterNonInternal();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\manager\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByCompany($modelClass = "\manager\models\Company")
    {
        return parent::getInvitationCreatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByStaff($modelClass = "\manager\models\Staff")
    {
        return parent::getInvitationCreatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByCompany($modelClass = "\manager\models\Company")
    {
        return parent::getInvitationUpdatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByStaff($modelClass = "\manager\models\Staff")
    {
        return parent::getInvitationUpdatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\manager\models\Request")
    {
        return parent::getRequest($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\manager\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\manager\models\Suggestion")
    {
        return parent::getSuggestion($modelClass);
    }
}