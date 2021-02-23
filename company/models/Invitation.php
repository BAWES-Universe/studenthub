<?php

namespace company\models;

use Yii;


class Invitation extends \common\models\Invitation
{
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\company\models\Candidate")
    {
        return $this->hasOne($modelClass::className(), ['candidate_id' => 'candidate_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByCompany($modelClass = "\company\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'invitation_created_by_company']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationCreatedByStaff($modelClass = "\company\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'invitation_created_by_staff']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByCompany($modelClass = "\company\models\Company")
    {
        return $this->hasOne($modelClass::className(), ['company_id' => 'invitation_updated_by_company']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInvitationUpdatedByStaff($modelClass = "\company\models\Staff")
    {
        return $this->hasOne($modelClass::className(), ['staff_id' => 'invitation_updated_by_staff']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRequest($modelClass = "\company\models\Request")
    {
        return $this->hasOne($modelClass::className(), ['request_uuid' => 'request_uuid']);
    }
}