<?php

namespace common\models\query;

use Yii;
use yii\helpers\ArrayHelper;
use common\models\CandidateIdCard;
use common\models\Store;

/**
 * This is the ActiveQuery class for [[Candidate]].
 *
 */
class CandidateQuery extends \yii\db\ActiveQuery 
{
    /**
     * @param $company
     * @return $this
     */
    public function filterCompany($company)
    {
        // create c bompany_id array from all sub companies and self

        $companies = $company->subCompanies;

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        // create store_id array 

        $stores = Store::find()
            ->andWhere(['in', 'company_id', $company_ids])
            ->all();

        $store_ids = ArrayHelper::map($stores, 'store_id', 'store_id');

        // return candidate list for given company 
        
        return $this->andWhere(['in', 'store_id', $store_ids]);
    }

    /**
     * @return $this
     */
    public function filterWithoutCard()
    {
        $cards = CandidateIdCard::find()
            ->all();

        $candidate_ids = ArrayHelper::map($cards, 'candidate_id', 'candidate_id');

        return $this->andWhere(['NOT IN', 'candidate_id', $candidate_ids]);
    }

    /**
     * @param $candidate_name
     * @return $this
     */
    public function filterName($candidate_name)
    {
        return $this->andWhere(['like', 'candidate_name', $candidate_name]);
    }

    /**
     * @return $this
     */
    public function filterAssigned()
    {
        return $this->andWhere('store_id > 0');    
    }

    /**
     * @return $this
     */
    public function filterNotAssigned()
    {
        return $this->andWhere('store_id IS NULL or store_id = 0');    
    }

    /**
     * @param $store_id
     * @return $this
     */
    public function filterStore($store_id)
    {
        return $this->andWhere(['store_id' => $store_id]);
    }

    /**
     * @param $country_id
     * @return $this
     */
    public function filterCountry($country_id)
    {
        return $this->andWhere(['country_id' => $country_id]);
    }

    /**
     * @return $this
     */
    public function idExpired()
    {
        return $this
            ->joinWith('candidate_id_card')
            ->andWhere('DATE(expiry_date) < DATE(NOW())');
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function idNeedGenerated()
    {
        return $this->andWhere('candidate_id NOT IN (select candidate_id from candidate_id_card)')
            ->all();   
    }

    /**
     * @return int|string
     */
    public function totalIdNeedGenerated()
    {
    	return $this->andWhere('candidate_id NOT IN (select candidate_id from candidate_id_card)')
    		->count();
    }

    /**
     * @return int|string
     */
    public function totalAssigned()
    {
    	return $this->andWhere('store_id > 0')
			->count();
	}

    /**
     * @return int|string
     */
    public function totalUnassigned()
    {
        return $this->andWhere('store_id IS NULL OR store_id = 0')
            ->count();
    }

    /**
     * @return $this
     */
    public function notDeleted()
    {
        return $this->andWhere(['deleted'=>0]);
    }

    public function selectField() {
        return $this->select('candidate_id,store_id,university_id,country_id,candidate_name,candidate_name_ar')
            ->addSelect('candidate_personal_photo,candidate_email,candidate_phone,candidate_address_line1')
            ->addSelect('candidate_birth_date,candidate_civil_id,candidate_civil_expiry_date,candidate_civil_photo_front')
            ->addSelect('candidate_civil_photo_back,candidate_created_at');
    }
}