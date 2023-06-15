<?php

namespace common\models\query;

use Yii;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\db\ActiveQuery;
use common\models\Transfer;


/**
 * This is the ActiveQuery class for [[Country]].
 *
 */
class TransferQuery extends ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%transfer}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%transfer}}.deleted' => 0]);
        return parent::one($db);
    }

    /**
     * @param $transfer_id
     * @return $this
     */
    public function filterParent($transfer_id)
    {
        return $this->andWhere(['transfer.parent_transfer_id' => $transfer_id]);
    }

    /**
     * filter transfers where transfer total(for candidate) not matching
     * transfer file total
     * @return TransferQuery
     */
    public function filterSuspicious()
    {
        return $this->andWhere(new Expression("transfer_id in (select DISTINCT(transfer_id) FROM `transfer_candidate` INNER JOIN `transfer_file_entry` on 
            `transfer_file_entry`.`credit_narrative` = `transfer_candidate`.`tc_id` 
            WHERE `transfer_file_entry`.`credit_amount` != `transfer_candidate`.`candidate_total`)"));

        /*
        might have unpaid candidate
        --------------------------------------
        return $this->andWhere('transfer.total != (select sum("credit_amount") from transfer_file_entry 
            where status="SUCCESS" AND debit_narrative=transfer.total)');
        */
    }

    /**
     * @param $id
     * @return $this
     */
    public function filterTransfer($id)
    {
        return $this->andWhere(['{{%transfer}}.transfer_id' => $id]);
    }

    /**
     * @param $company_id
     * @return $this
     */
    public function filterCompanyId($company_id)
    {
        return $this->andWhere(['{{%transfer}}.company_id' => $company_id]);
    }

    /**
     * @param $company_name
     * @return $this
     */
    public function filterCompany($company_name)
    {
        return $this->andWhere(['like', '{{%company}}.company_name', $company_name]);
    }

    /**
     * @param $transfer_status
     * @return $this
     */
    public function filterStatus($transfer_status)
    {
        return $this->andWhere(['{{%transfer}}.transfer_status' => $transfer_status]);
    }

    /**
     * filter transfers where company paid
     * @return TransferQuery
     */
    public function filterPaymentReceived()
    {
        return $this->andWhere(['in', '{{%transfer}}.transfer_status', [
            Transfer::STATUS_PAYMENT_SENT,
            Transfer::STATUS_SALARY_DISTRIBUTION_IN_PROGRESS,
            Transfer::STATUS_TRANSFER_COMPLETE
        ]]);
    }

    /**
     * Transfer for login company / his child
     * @param $company
     * @return $this
     */
    public function filterCurrentCompany($company)
    {
        $companies = $company->subCompanies;

        $company_ids = ArrayHelper::map( $companies, 'company_id', 'company_id' );

        $company_ids[] = $company->company_id;
        return $this->andWhere([ 'in', '{{%transfer}}.company_id', $company_ids ]);
    }

    /**
     * @return $this
     */
    public function filterSameRate() {
        return $this->joinWith(['transferCandidates'], true, 'inner join')
            ->andWhere(new Expression("transfer_candidate.candidate_hourly_rate=transfer_candidate.company_hourly_rate"));
    }

    /**
     * @return $this
     */
    public function filterNoProfit() {
        return $this->joinWith(['transferCandidates'], true, 'inner join')
            ->andWhere(new Expression("transfer_candidate.candidate_total=transfer_candidate.company_total"));
    }

    /**
     * Filter parent transfer
     */
    public function isParentTransfer()
    {
        return $this->andWhere('parent_transfer_id IS NULL');
    }

    /**
     * @return $this
     */
    public function companyJoin()
    {
        return $this->joinWith('company');
    }

    /**
     * @return $this
     */
    public function transferCandidateJoin()
    {
        return $this->joinWith('transferCandidates');
    }

    /**
     * return transfer decreasing order
     * @return $this
     */
    public function decreasingOrder() {
        return $this->orderBy('transfer_id DESC');
    }

    /**
     * @param $date
     * @return TransferQuery
     */
    public function startDate($date)
    {
        return $this->andWhere("DATE(transfer_created_at) > '$date'");
    }

    /**
     * @param $date
     * @return TransferQuery
     */
    public function endDate($date)
    {
        return $this->andWhere("DATE(transfer_created_at) < '$date'");
    }
}
