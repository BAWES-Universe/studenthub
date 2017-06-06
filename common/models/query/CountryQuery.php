<?php

namespace common\models\query;
use Yii;

/**
 * This is the ActiveQuery class for [[Country]].
 *
 */
class CountryQuery extends \yii\db\ActiveQuery
{
	public function listWithCandidateCount()
	{
		return $this->select([
				'country.*', 
				'COUNT(candidate.candidate_id) as total_candidates'
			])
            ->leftJoin('candidate', 'candidate.country_id = country.country_id')
            ->groupBy('country.country_id')
            ->orderBy('total_candidates DESC, country_name_en')
            ->asArray();
	}
}
	