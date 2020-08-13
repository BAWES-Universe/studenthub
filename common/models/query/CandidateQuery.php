<?php

namespace common\models\query;

use Yii;
use yii\helpers\ArrayHelper;
use common\models\CandidateIdCard;
use common\models\Store;

/**
 * This is the ActiveQuery class for [[Candidate]].
 */
class CandidateQuery extends \yii\db\ActiveQuery 
{
    /**
     * @param $company
     * @return $this
     */
    public function filterCompany($company)
    {
        // create company_id array from all sub companies and self

        $companies = $company->subCompanies;

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        // create store_id array 

        $stores = Store::find()
            ->andWhere(['in', 'company_id', $company_ids])
            ->all();

        $store_ids = ArrayHelper::map($stores, '{{%candidate}}.store_id', 'store_id');

        // return candidate list for given company 
        
        return $this->andWhere(['in', '{{%candidate}}.store_id', $store_ids]);
    }

    /**
     * @param $candidate_name
     * @return $this
     */
    public function filterName($candidate_name)
    {
        return $this->andWhere(['like', '{{%candidate}}.candidate_name', $candidate_name]);
    }

    /**
     * @return $this
     */
    public function filterAssigned()
    {
        return $this->andWhere('{{%candidate}}.store_id > 0');
    }

    /**
     * @return $this
     */
    public function filterNotAssigned()
    {
        return $this->andWhere('{{%candidate}}.store_id IS NULL or {{%candidate}}.store_id = 0');
    }

    /**
     * @param $store_id
     * @return $this
     */
    public function filterStore($store_id)
    {
        return $this->andWhere(['{{%candidate}}.store_id' => $store_id]);
    }

    /**
     * @param $country_id
     * @return $this
     */
    public function filterCountry($country_id)
    {
        return $this->andWhere(['{{%candidate}}.country_id' => $country_id]);
    }

    /**
     * @param $university_id
     * @return $this
     */
    public function filterUniversity($university_id)
    {
        return $this->andWhere(['{{%candidate}}.university_id' => $university_id]);
    }

    /**
     * @return $this
     */
    public function idExpired()
    {
        return $this
            ->joinWith('candidateIdCard')
            ->andWhere('DATE(expiry_date) < DATE(NOW())');
    }

    /**
     * @return array|\yii\db\ActiveRecord[]
     */
    public function idNeedGenerated()
    {
        return $this->andWhere('candidate_id NOT IN (select candidate_id from candidate_id_card)');
    }

    /**
     * @return int|string
     */
    public function totalAssigned()
    {
    	return $this->andWhere('{{%candidate}}.store_id > 0');
	}

    /**
     * @return int|string
     */
    public function totalUnassigned()
    {
        return $this->andWhere('{{%candidate}}.store_id IS NULL OR {{%candidate}}.store_id = 0');
    }
 /**
     * @return int|string
     */
    public function withBankInfo()
    {
        return $this->andWhere('{{%candidate}}.bank_id IS NULL');
    }

    /**
     * @return $this
     */
    public function notDeleted()
    {
        return $this->andWhere(['{{%candidate}}.deleted'=>0]);
    }

    /**
     * @param $status
     * @return $this
     */
    public function byApprovalStatus($status = 0) {
        return $this->andWhere(['{{%candidate}}.approved' => $status]);
    }

    /**
     * @param $id
     * @return CandidateQuery
     */
    public function filterById($id)
    {
        return $this->andWhere(['{{%candidate}}.candidate_id'=>$id]);
    }
}
