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
    /**
     * @return InvitationQuery
     */
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
        return $this->andWhere(['IN', 'request.request_status', [Request::STATUS_STARTED, Request::STATUS_PENDING, Request::STATUS_RE_WORK]]);
    }

    /**
     * @return InvitationQuery
     */
    public function accepted()
    {
        return $this->andWhere(['invitation_status' => Invitation::STATUS_ACCEPTED]);
    }

    /**
     * @return InvitationQuery
     */
    public function rejected()
    {
        return $this->andWhere(['invitation_status' => Invitation::STATUS_REJECTED]);
    }

    /**
     * @param $request_uuid
     * @return InvitationQuery
     */
    public function filterRequest($request_uuid) {
        return $this->andWhere (['request_uuid' => $request_uuid]);
    }

    /**
     * @param $story_uuid
     * @return InvitationQuery
     */
    public function filterStory($story_uuid) {
        return $this->andWhere (['story_uuid' => $story_uuid]);
    }
}
