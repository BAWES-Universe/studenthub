<?php

namespace common\models\query;

use company\models\Request;
use common\models\Invitation;


/**
 * This is the ActiveQuery class for [[Invitation]].
 *
 */
class InvitationQuery extends \yii\db\ActiveQuery
{
    public function filterInvited()
    {
        return $this->andWhere (['invitation_status' => Invitation::STATUS_INVITED]);
    }

    /**
     * active requests
     * @return RequestQuery
     */
    public function activeRequest()
    {
        return $this->andWhere(['IN', 'request.request_status', [Request::STATUS_STARTED, 'pending']]);
    }

    public function accepted()
    {
        return $this->andWhere(['invitation_status' => Invitation::STATUS_ACCEPTED]);
    }

    public function rejected()
    {
        return $this->andWhere(['invitation_status' => Invitation::STATUS_REJECTED]);
    }

    public function filterRequest($request_uuid) {
        return $this->andWhere (['request_uuid' => $request_uuid]);
    }
}
