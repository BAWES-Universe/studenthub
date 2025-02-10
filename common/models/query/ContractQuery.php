<?php

namespace common\models\query;


use common\models\Store;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class ContractQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%contract}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param $q
     * @param $db
     * @return bool|int|string|null
     */
    public function count($q = '*', $db = null)
    {
        $this->andWhere(['{{%contract}}.deleted' => 0]);
        return parent::count($q, $db);
    }

    /**
     * compare candidate id
     * @param $candidate_id
     * @return $this
     */
    public function filterCandidate($candidate_id) {
        return $this->andWhere(['candidate_id'=>$candidate_id]);
    }

    /**
     * compare candidate id
     * @param $candidate_id
     * @return $this
     */
    public function filterStaff($staff_id) {
        return $this->andWhere(['created_by'=>$staff_id]);
    }

    /**
     * compare date
     * @param $date
     * @return $this
     */
    public function filterDate($date) {
        return $this->andWhere(['start_date'=>$date]);
    }

    /**
     * filter by null end date
     * @return $this
     */
    public function emptyEndDate() {
        return $this->andWhere('end_date is null');
    }

    public function filterByJoiningDate($startDate = null, $endDate = null, $companyID = null)
    {
        if ($startDate) {
            $startDate = date('Y-m-d', strtotime($startDate));
            $this->andWhere("DATE(contract.start_date) >= '$startDate'");
        }
        if ($endDate) {
            $endDate = date('Y-m-d', strtotime($endDate));
            $this->andWhere("DATE(contract.start_date) <= '$endDate'");
        }
        if ($companyID) {
            $this->andWhere(["`contract`.`parent_company_id`" => $companyID]);
        }
        return $this;
    }

    /**
     * @param $start_date
     * @param $end_date
     * @return CandidateWorkingDateQuery
     */
    public function filterByDate($date) {

        return $this->andWhere(new Expression("DATE(contract.start_date) <= DATE('".$date."') AND
            (contract.end_date IS NULL OR DATE(contract.end_date) >= DATE('".$date."'))"));

        /*return $this->andWhere (new Expression("
            DATE('".date('Y-m-d', strtotime($date))."') BETWEEN DATE(contract.start_date) 
            AND DATE(contract.end_date)
        "));*/
    }

    /**
     * @return int|string
     */
    public function totalAssigned()
    {
        return $this->joinWith('candidate')
                ->andWhere('{{%candidate}}.store_id > 0');
    }

    /**
     * @return CandidateWorkHistoryQuery
     */
    public function notDeleted() {
        return $this->joinWith('candidate')
            ->andWhere(['{{%candidate}}.deleted'=>0]);
    }

    /**
     * @param $companyID
     * @return void
     */
    public function filterCompany($companyID) {
        $this->andWhere(["`contract`.`parent_company_id`" => $companyID]);
    }

    /**
     * @param $company
     * @return CandidateWorkHistoryQuery
     */
    public function filterCompanyByCandidate($company) {
        // create company_id array from all sub companies and self
        $companies = $company->subCompanies;

        $company_ids = ArrayHelper::map($companies, 'company_id', 'company_id');

        $company_ids[] = $company->company_id;

        // create store_id array
        $stores = Store::find()
            ->andWhere(['in', 'company_id', $company_ids])
            ->all();

        $store_ids = ArrayHelper::map($stores, 'store_id', 'store_id');
        $this->joinWith('candidate');
        $this->andWhere(['in', '{{%candidate}}.store_id', $store_ids]);
        return $this->groupBy('{{%candidate}}.candidate_id');
    }

    /**
     * @param $candidate_name
     * @return $this
     */
    public function filterName($candidate_name)
    {
        return $this->joinWith(['candidate'])->andWhere(['like', '{{%candidate}}.candidate_name', $candidate_name]);
    }

    /**
     * @param $candidate_email
     * @return $this
     */
    public function filterEmail($candidate_email)
    {
        return $this->joinWith(['candidate'])->andWhere(['like', '{{%candidate}}.candidate_email', $candidate_email]);
    }

    /**
     * @param $date
     * @return $this
     */
    public function startDate($date)
    {
        return $this->andWhere("DATE(contract.start_date) > '$date'");
    }

    /**
     * @param $date
     * @return $this
     */
    public function endDate($date, $working_time)
    {
        if (!$working_time) { // in case if we just want to check assigned candidate in time slot
            return $this->andWhere("DATE(contract.start_date) < '$date'");
        } else  { // in case if we want to check candidate working slot
            return $this->andWhere("DATE(contract.end_date) < '$date'");
        }
    }

    /**
     * @param $currency
     * @return $this
     */
    public function filterCurrency($currency) {
        return $this->joinWith(['parentCompany'])
            ->andWhere(['company.currency_code' => $currency]);
    }
}