<?php

namespace common\models\query;

use Yii;
use yii\helpers\ArrayHelper;
use common\models\CandidateIdCard;

/**
 * This is the ActiveQuery class for [[Candidate]].
 *
 */
class CandidateQuery extends \yii\db\ActiveQuery 
{
    public function filterWithoutCard()
    {
        $cards = CandidateIdCard::find()
            ->all();

        $candidate_ids = ArrayHelper::map($cards, 'candidate_id', 'candidate_id');

        return $this->where(['NOT IN', 'candidate_id', $candidate_ids]);
    }        

    public function filterName($candidate_name) 
    {
        return $this->andWhere(['like', 'candidate_name', $candidate_name]);
    }
    
    public function filterAssigned()
    {
        return $this->andWhere('store_id > 0');    
    }

    public function filterNotAssigned()
    {
        return $this->andWhere('store_id IS NULL or store_id = 0');    
    }

    public function filterStore($store_id) 
    {
        return $this->where(['store_id' => $store_id]);
    }

    public function filterCountry($country_id) 
    {
        return $this->where(['country_id' => $country_id]);
    }

    public function idExpired()
    {
        return $this
            ->innerJoin('candidate_id_card', 'candidate_id_card.candidate_id = candidate.candidate_id')
            ->where('DATE(expiry_date) < DATE(NOW())');
    }

    public function idNeedGenerated()
    {
        return $this->where('candidate_id NOT IN (select candidate_id from candidate_id_card)')
            ->all();   
    }

    public function totalIdNeedGenerated() 
    {
    	return $this->where('candidate_id NOT IN (select candidate_id from candidate_id_card)')
    		->count();
    }

    public function totalAssigned()
    {
    	return $this->where('store_id > 0')
			->count();
	}

    public function totalUnassigned()
    {
        return $this->where('store_id IS NULL OR store_id = 0')
            ->count();
    }            
}