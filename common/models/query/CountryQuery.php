<?php

namespace common\models\query;

use Yii;
use common\models\Country;


/**
 * This is the ActiveQuery class for [[Country]].
 *
 */
class CountryQuery extends \yii\db\ActiveQuery {

    /**
     * @return $this
     */
    public function listWithCandidateCount() {
        return $this->select([
                'country.*',
                'COUNT(candidate.candidate_id) as total_candidates'
            ])
            ->leftJoin('candidate', 'candidate.country_id = country.country_id')
            ->groupBy('country.country_id')
            ->orderBy('total_candidates DESC, country_name_en')
            ->asArray();
    }

    /**
     * @return $this
     */
    public function joinCandidate() {
        return $this->leftJoin('candidate', 'candidate.country_id = country.country_id')->andwhere(['{{%candidate}}.deleted' => 0]);
    }

    /**
     * Don't list countries added by google map
     * @return CountryQuery
     */
    public function filterNotFromGoogle() {
        return $this->andWhere(['country_from_google_map' => Country::NOT_FROM_GOOGLE_MAP]);
    }

    /**
     * @param $name
     * @return CountryQuery
     */
    public function filterName($name)
    {
        return $this->andWhere(
            ['or',
                ['like', 'country_name_en', $name],
                ['like', 'country_name_ar', $name]
            ]
        );
    }
}
