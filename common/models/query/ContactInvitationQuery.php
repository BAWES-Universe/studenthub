<?php

namespace common\models\query;

use Yii;
use common\models\Candidate;
use common\models\ContactInvitation;

/**
 * JobQuery extends ActiveQuery, allowing easier filtering of candidates
 */
class ContactInvitationQuery extends \yii\db\ActiveQuery {

    /**
     * @inheritdoc
     * @return Candidate[]|array
     */
    public function all($db = null)
    {
        $this->andWhere(['contact_invitation.is_deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Candidate|array|null
     */
    public function one($db = null)
    {
        $this->andWhere(['contact_invitation.is_deleted' => 0]);
        return parent::one($db);
    }

    /**
     * current agent
     * @return $this
     */
    public function filterByCurrentContact()
    {
        return $this->andWhere(['email_to_invite' => Yii::$app->user->identity->email]);
    }

    /**
     * remove already active + pending 
     * @return type
     */
    public function filterByActiveInvitations() {
        return $this->andWhere([
            'OR',
            [
                'accepted' => ContactInvitation::ACCEPTED_TRUE
            ], 
            'accepted IS NULL'
        ]);
    }
    
    /**
     * accepted invitation
     * @return $this
     */
    public function filterByAcceptedInvitations()
    {
        return $this->andWhere(['accepted' => ContactInvitation::ACCEPTED_TRUE]);
    }
    
    /**
     * pending invitation
     * @return $this
     */
    public function filterByRejectedInvitations()
    {
        return $this->andWhere(['accepted' => ContactInvitation::ACCEPTED_FALSE]);
    }
    
    /**
     * pending invitation
     * @return $this
     */
    public function filterByPendingInvitations()
    {
        return $this->andWhere('accepted IS NULL');
    }

    /**
     * @param $email
     * @return ContactInvitationQuery
     */
    public function filterByEmail($email) {
        return $this->andWhere(['email_to_invite' => $email]);
    }

    /**
     * @param $CompanyID
     * @return ContactInvitationQuery
     */
    public function filterByCompanyId($company_id) {
        return $this->andWhere(['company_id' => $company_id]);
    }
}
