<?php

namespace company\models;

use Yii;

class CandidateWorkLogFeedback extends \common\models\CandidateWorkLogFeedback
{
    /**
     * @param $insert
     * @return bool
     */
    public function beforeSave($insert)
    {
        if(!parent::beforeSave($insert)) {
            return false;
        }

        //validate candidate_id, store_id, company_id

        $store_id = $this->store_id;

        //get store_id from candidate_history of date provided
        if (empty($this->store_id)) {
            $history = \common\models\CandidateWorkHistory::find()
                ->andWhere(['candidate_id' => $this->candidate_id])
                ->filterByDate($this->date)
                ->one();

            if ($history)
                $store_id = $history->store_id;
        }

        if (empty($this->store_id))
           $store_id = $this->candidate->store_id;

        //make sure store belongs to login user

        $company = Yii::$app->companyManager->getCompany();

        if (isset($company->subCompanies) && count($company->subCompanies)>0) {
            $query = $company
                ->getSubCompanyStores();
//                ->getSubCompanies();
        } else {
            $query = $company
                ->getStores();
        }

        $query->andWhere(['store_id' => $store_id]);

        $store = $query->one();

        if(!$store) {
            $this->addError("store_id", "Invalid store");
            return false;
        }

        $this->store_id = $store->store_id;
        $this->company_id = $store->company_id;

        return true;
    }
}