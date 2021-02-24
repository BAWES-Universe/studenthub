<?php

namespace staff\models;


class Invitation extends \common\models\Invitation
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        return parent::fields ();
    }

    /**
     * Gets query for [[Invitation]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelName = '\staff\models\Note')
    {
        return parent::getNotes($modelName);
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
    public function getInvitationCreatedByCompany($modelClass = "\staff\models\Company")
    {
        return parent::getInvitationCreatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByStaff($modelClass = "\staff\models\Staff")
    {
        return parent::getInvitationCreatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByCompany($modelClass = "\staff\models\Company")
    {
        return parent::getInvitationUpdatedByCompany($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByStaff($modelClass = "\staff\models\Staff")
    {
        return parent::getInvitationUpdatedByStaff($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\staff\models\Request")
    {
        return parent::getRequest($modelClass);
    }
}
