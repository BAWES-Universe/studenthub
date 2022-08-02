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

    /**
     * @param $date
     * @return RequestQuery
     */
	public function startDate($date)
    {
        return $this->andWhere("DATE(request_created_datetime) > '".$date."'");
    }

    /**
     * @param $date
     * @return RequestQuery
     */
    public function endDate($date)
    {
        return $this->andWhere("DATE(request_updated_datetime) < '".$date."'");
    }

    /**
     * @param $type
     * @return RequestQuery
     */
    public function filterByType($type)
    {
        return $this->andWhere(['request_position_type' => $type]);
    }

    /**
     * @return RequestQuery
     */
    public function handleByStaff()
    {
        return $this->andWhere(['!=','request_created_by', 0]);
    }

    public function filterByStaff($staff_id){
        return $this->andWhere(['request_created_by' => $staff_id]);
    }

    /**
     * requests that need attentions
     * @return RequestQuery
     */
    public function needUpdate()
    {
        return $this->activeRequest();
            //last 1 hour
        // removed as not showing all data
//            ->andWhere(
//                new \yii\db\Expression(
//                    "request_updated_datetime < DATE_SUB(NOW(),INTERVAL 24 HOUR) OR request_updated_datetime = request_created_datetime"
//                )
//            );
    }

    /**
     * filter by query string
     */
    public function filterByKeyword($keyword)
    {
        return $this->andWhere([
            'OR',
            ['like', 'request_job_description', $keyword],
            ['like', 'request_compensation', $keyword],
            ['like', 'request_additional_info', $keyword],
            ['like', 'request_location', $keyword],
        ]);
    }

    /**
     * pending requests
     * @return RequestQuery
     */
    public function pendingRequest()
    {
        return $this->andWhere(['request.request_status' => Request::STATUS_PENDING]);
    }

    /**
     * active requests
     * @return RequestQuery
     */
    public function activeRequest()
    {
        return $this->andWhere(['IN', 'request.request_status', [Request::STATUS_STARTED,Request::STATUS_PENDING,Request::STATUS_RE_WORK]]);
    }

    /**
     * active requests
     * @return RequestQuery
     */
    public function orderByFollowupInterval()
    {
//        SELECT request_uuid, num_hours_followup_interval, (num_hours_followup_interval*60) as min, (TIMESTAMPDIFF(MINUTE, request_updated_datetime,CURRENT_TIMESTAMP())- (num_hours_followup_interval*60)) as remain, TIMESTAMPDIFF(MINUTE, request_updated_datetime,CURRENT_TIMESTAMP()) as diff from request order by (TIMESTAMPDIFF(MINUTE, request_updated_datetime,CURRENT_TIMESTAMP())-(num_hours_followup_interval*60)) DESC
        return $this->addOrderBy('(TIMESTAMPDIFF(MINUTE, request_updated_datetime,CURRENT_TIMESTAMP())-(num_hours_followup_interval*60)) DESC');
    }

    public function orderByDateDESC() {
        return $this->addOrderBy('request_created_datetime DESC');
    }
}
