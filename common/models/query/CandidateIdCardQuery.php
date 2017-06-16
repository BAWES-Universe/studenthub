<?php

namespace common\models\query;
use Yii;

/**
 * This is the ActiveQuery class for [[CandidateIdCard]].
 *
 */
class CandidateIdCardQuery extends \yii\db\ActiveQuery
{
	public function idExpired()
	{
		return $this->andWhere('DATE(expiry_date) < DATE(NOW())')
			->all();
	}

	public function totalIdExpired()
	{
		return $this->andWhere('DATE(expiry_date) < DATE(NOW())')
            ->count();
	}
}