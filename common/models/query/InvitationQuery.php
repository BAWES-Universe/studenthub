<?php

namespace common\models\query;

use Yii;
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
}