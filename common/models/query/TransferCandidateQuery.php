<?php

namespace common\models\query;

use common\models\Transfer;

/**
 * This is the ActiveQuery class for [[TransferCandidate]].
 *
 */
class TransferCandidateQuery extends \yii\db\ActiveQuery {

    /**
     * @inheritdoc
     * @return CandidateWorkHistory[]|array
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%transfer_candidate}}.deleted'=>0]);
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CandidateWorkHistory|array|null
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%transfer_candidate}}.deleted'=>0]);
        return parent::one($db);
    }

    /**
     * @return $this
     */
    public function filterPaid() {
        return $this->andWhere([
            '{{%transfer_candidate}}.paid' => 1
        ]);
    }

    /**
     * @return $this
     */
    public function filterUnpaid() {
        return $this->andWhere([
            '{{%transfer_candidate}}.paid' => 0
        ]);
    }

    /**
     * @param $company_id
     * @return $this
     */
    public function filterCompanyId($company_id) {
        return $this->andWhere(['{{%transfer_candidate}}.company_id' => $company_id]);
    }

    /**
     * return candidate by candidate id
     * @param $candidate_id
     * @return $this
     */
    public function filterCandidate($candidate_id) {
        return $this->andWhere(['{{%transfer_candidate}}.candidate_id' => $candidate_id]);
    }

    /**
     * @return $this
     */
    public function filterPaidInvoice() {
        return $this->andWhere([
            '{{%invoice}}.invoice_status' => 'paid',
            '{{%invoice}}.deleted' => 0
        ]);
    }

    /**
     * Return profit for transfer
     * transfer cost will be on admin
     */
    public function profit() {
        
        $expression = "
            (
                (
                    {{%transfer_candidate}}.company_hourly_rate - {{%transfer_candidate}}.candidate_hourly_rate 
                ) 
                * 
                {{%transfer_candidate}}.hours
            ) 
            - 
            {{%transfer_candidate}}.transfer_cost
            +
            {{%transfer_candidate}}.bonus_commission";
                
        return $this->sum($expression);        
    }

    /**
     * Return candidates who not got paid
     * but his employer have paid to admin
     */
    public function payable() {
        return $this->joinWith('transfer')
            ->where(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            ->andWhere('parent_transfer_id IS NULL')//only parent transfers 
            ->filterUnpaid(); //unpaid candidate
    }

    /**
     * Return candidates who not got paid or paid
     * but his employer have paid to admin
     */
    public function payableWithPaid() {
        return $this->joinWith('transfer')
            ->andWhere(['IN', 'transfer.transfer_status', [Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS, Transfer::STATUS_TRANSFER_COMPLETE]])
            ->andWhere('transfer.parent_transfer_id IS NULL'); //only parent transfers
    }

    /**
     * @param $company_id
     * @return $this
     */
    public function groupByCompany($company_id) {
        return $this->select('{{%transfer_candidate}}.company_id')
            ->distinct()
            //hide main company 
            ->andWhere(['!=', '{{%transfer_candidate}}.company_id', $company_id]);
    }

    /**
     * Return candidates for transfer
     * @param $transfer_id
     * @return $this
     */
    public function candidatesByTransfer($transfer_id) {
        return $this->andWhere([
            '{{%transfer_candidate}}.transfer_id' => $transfer_id
        ]);
    }

    /**
     * Total paid in transfer
     * @return int|string
     */
    public function totalPaid() {
        return $this->andWhere(['paid' => 1])
            ->count();
    }

    /**
     * Total unpaid in transfer
     * @return int|string
     */
    public function totalUnpaid() {
        return $this->andWhere(['paid' => 0])
            ->count();
    }

    /**
     * @param $status
     * @return $this
     */
    public function totalPaymentStatus($status) {
        return $this->andWhere(['paid' => $status]);
    }

    /**
     * @param $company_name
     * @return $this
     */
    public function filterCompany($company_name) {
        return $this->andWhere([
            'like',
            'company_name',
            $company_name
        ]);
    }

    /**
     * @param $store_name
     * @return $this
     */
    public function filterStore($store_name) {
        return $this->andWhere([
            'like',
            'store_name',
            $store_name
        ]);
    }

    /**
     * @param $tc_id
     * @return $this
     */
    public function filterInPrimaryKey($tc_id) {
        return $this->andWhere(['in', 'tc_id', $tc_id]);
    }

    /**
     * Return unpaid candidate list for a given transfer
     * @param $transfer_id
     * @return $this
     */
    public function unpaid($transfer_id) {
        return $this->andWhere([
            '{{%transfer_candidate}}.paid' => 0,
            'transfer_id' => $transfer_id
        ]);
    }
    
    /**
     * filter candidate who will get paid
     * @return this
     */
    public function willGetPaid() {
        return $this->andWhere([
            'OR',
            new \yii\db\Expression('{{%transfer_candidate}}.bonus IS NOT NULL AND {{%transfer_candidate}}.bonus > 0'),
            new \yii\db\Expression('{{%transfer_candidate}}.hours IS NOT NULL AND {{%transfer_candidate}}.hours > 0'),
        ]);
    }
}
