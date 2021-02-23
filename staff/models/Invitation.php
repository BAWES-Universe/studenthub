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
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\staff\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByCompany($modelClass = "\staff\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'invitation_created_by_company']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByStaff($modelClass = "\staff\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'invitation_created_by_staff']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByCompany($modelClass = "\staff\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'invitation_updated_by_company']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByStaff($modelClass = "\staff\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'invitation_updated_by_staff']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\staff\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }
}
