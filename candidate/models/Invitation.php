<?php
namespace candidate\models;

use Yii;


class Invitation extends \common\models\Invitation
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\common\models\Note")
    {
        return parent::getNotes($modelClass);
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
    public function getInvitationCreatedByCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getInvitationCreatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByStaff($modelClass = "\candidate\models\Staff")
    {
        return parent::getInvitationCreatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getInvitationUpdatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByStaff($modelClass = "\candidate\models\Staff")
    {
        return parent::getInvitationUpdatedByStaff($modelClass);
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
    public function getCompany($modelClass = "\candidate\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSuggestion($modelClass = "\candidate\models\Suggestion")
    {
        return parent::getSuggestion($modelClass);
    }
}
