<?php

namespace common\models\query;

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
}
