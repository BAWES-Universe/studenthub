<?php

namespace common\models\query;

use common\models\Invitation;
use company\models\Request;
use Yii;
use yii\db\ActiveQuery;


/**
 * This is the ActiveQuery class for [[Request]].
 *
 */
class RequestQuery extends ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

	public function startDate($date)
    {
        return $this->andWhere("DATE(request_created_datetime) > '$date'");
    }

    public function endDate($date)
    {
        return $this->andWhere("DATE(request_updated_datetime) < '$date'");
    }

    public function filterByType($type)
    {
        return $this->andWhere(['request_position_type' => $type]);
    }

    public function handleByStaff()
    {
        return $this->andWhere(['!=','request_created_by', 0]);
    }

    /**
     * active requests
     * @return RequestQuery
     */
    public function activeRequest()
    {
        return $this->andWhere(['IN', 'request.request_status', [Request::STATUS_STARTED, 'pending']]);
    }

    /**
     * active requests
     * @return RequestQuery
     */
    public function orderByFollowupInterval()
    {
        return $this->orderBy('num_hours_followup_interval DESC');
    }
}
