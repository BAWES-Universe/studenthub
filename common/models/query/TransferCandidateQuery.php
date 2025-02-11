<?php

namespace common\models\query;

use common\models\Transfer;
use yii\db\Expression;

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
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function count($q = '*', $db = null)
    {
        $this->andWhere(['{{%transfer_candidate}}.deleted' => 0]);
        return parent::count($q, $db);
    }

    /**
     * filter transfers where company paid
     * @return TransferQuery
     */
    public function filterStatus($status)
    {
        return $this->joinWith(['transfer'])
            ->andWhere(['{{%transfer}}.transfer_status' => $status]);
    }

    /**
     * @param $date
     * @return TransferQuery
     */
    public function startDate($date)
    {
        return $this->joinWith(['transfer'])->andWhere("DATE(transfer_created_at) > '$date'");
    }

    /**
     * @param $date
     * @return TransferQuery
     */
    public function endDate($date)
    {
        return $this->joinWith(['transfer'])->andWhere("DATE(transfer_created_at) < '$date'");
    }

    public function filterDuplicate() {
        //select t1.tc_id, 1.transfer_id, t2.tc_id as duplicate_tc_id, t2.transfer_id as duplicate_transfer_id from transfer_candidate t1
        // left join transfer_candidate t2 on t1.candidate_id = t2.candidate_id AND
        // MONTH(t1.tc_created_at) = MONTH(t2.tc_created_at) AND YEAR(t1.tc_created_at) = YEAR(t2.tc_created_at) AND
        // t1.company_id = t2.company_id AND t2.deleted = 0 where t1.tc_id != t2.tc_id AND t1.deleted=0;

        /**
         * [
        "transfer_candidate.candidate_id" => "t2.candidate_id",
        // [new Expression("MONTH(transfer_candidate.tc_created_at) = MONTH(t2.tc_created_at) AND YEAR(transfer_candidate.tc_created_at) = YEAR(t2.tc_created_at)")],
        "transfer_candidate.company_id" => "t2.company_id",
        "t2.deleted" => 0
        ]
         */
        return $this->leftJoin(["t2" => "transfer_candidate"], "transfer_candidate.candidate_id = t2.candidate_id AND
         MONTH(transfer_candidate.tc_created_at) = MONTH(t2.tc_created_at) AND YEAR(transfer_candidate.tc_created_at) = YEAR(t2.tc_created_at) AND
         transfer_candidate.company_id = t2.company_id AND t2.deleted = 0")
            //->andWhere(new Expression("MONTH(transfer_candidate.tc_created_at) = MONTH(t2.tc_created_at) AND YEAR(transfer_candidate.tc_created_at) = YEAR(t2.tc_created_at)"))
            ->andWhere(["!=", "transfer_candidate.tc_id", new Expression("t2.tc_id")]);
    }

    /**
     * filter transfers where company paid
     * @return TransferQuery
     */
    public function filterPaymentReceived()
    {
        return $this->joinWith(['transfer'])
            ->andWhere(['in', '{{%transfer}}.transfer_status', [
                Transfer::STATUS_PAYMENT_SENT,
                Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                Transfer::STATUS_TRANSFER_COMPLETE
            ]]);
    }

    /**
     * @return $this
     */
    public function filterSameRate() {
        return $this->andWhere(new Expression("transfer_candidate.candidate_hourly_rate=transfer_candidate.company_hourly_rate"));
    }

    /**
     * @return $this
     */
    public function filterNoProfit() {
        return $this->andWhere(new Expression("transfer_candidate.candidate_total=transfer_candidate.company_total"));
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
                + {{%transfer_candidate}}.transfer_cost
                * 
                {{%transfer_candidate}}.hours
            ) 
            +
            {{%transfer_candidate}}.bonus_commission";
                
        return $this->sum($expression);        
    }

    /**
     * Return candidates who not got paid
     * but his employer have paid to admin
     */
    public function payable() {
        return $this->joinWith(['transfer','candidate'])
            ->andWhere(['transfer_status' => Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS])
            /*
            ->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                //    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])*/
            ->andWhere('parent_transfer_id IS NULL')//only parent transfers
            ->filterUnpaid(); //unpaid candidate
    }

    /**
     * Return candidates who not got paid or paid
     * but his employer have paid to admin
     */
    public function payableWithPaid() {
        return $this->joinWith('transfer')
            ->andWhere([
                'IN',
                'transfer.transfer_status', [
                    Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
                    Transfer::STATUS_TRANSFER_COMPLETE
                ]
            ])
            ->andWhere('transfer.parent_transfer_id IS NULL'); //only parent transfers
    }

    /**
     * transfers with bank details
     * @return TransferCandidateQuery
     */
    public function havingBankInfo() {
        return $this->andWhere(new Expression(
            'transfer_candidate.bank_id IS NOT NULL AND 
            transfer_candidate.transfer_benef_iban IS NOT NULL AND 
            transfer_candidate.transfer_benef_name IS NOT NULL')
        );
    }

    public function missingBankInfo() {
        return $this->andWhere(new Expression(
                'transfer_candidate.bank_id IS NULL OR 
            transfer_candidate.transfer_benef_iban IS NULL OR 
            transfer_candidate.transfer_benef_name IS NULL')
        );
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
     * todo: test function generated query
     * filter candidate who will get paid
     * @return this
     */
    public function willGetPaid() {
        return $this->andWhere(new \yii\db\Expression('{{%transfer_candidate}}.company_total > 0'));

        /*return $this->andWhere([
            'OR',
            new \yii\db\Expression('{{%transfer_candidate}}.bonus IS NOT NULL AND {{%transfer_candidate}}.bonus > 0'),
            new \yii\db\Expression('{{%transfer_candidate}}.hours IS NOT NULL AND {{%transfer_candidate}}.hours > 0'),
        ]);*/
    }

    /**
     * @return $this
     */
    public function civilIdExpired()
    {
        return $this->joinWith(['candidate'])
            ->andWhere('DATE(candidate_civil_expiry_date) < DATE(NOW())');
    }

    /**
     * @return CandidateQuery
     */
    public function activeCivilId()
    {
        return $this->joinWith(['candidate'])
            ->andWhere('DATE({{%candidate}}.candidate_civil_expiry_date) >= DATE(NOW())');
    }

    /**
     * @return TransferCandidateQuery
     */
    public function incompleteProfile() {
        return $this->joinWith(['candidate'])
            ->andWhere(['is_incomplete_profile' => true]);
    }

    /**
     * @return TransferCandidateQuery
     */
    public function completeProfile() {
        return $this->joinWith(['candidate'])
            ->andWhere(['is_incomplete_profile' => false]);
    }
}
