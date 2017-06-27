<?php

namespace common\models\query;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[TransferCandidate]].
 *
 */
class TransferCandidateQuery extends \yii\db\ActiveQuery
{
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return $this
     */
    public function filterPaid()
    {
        return $this->andWhere([
                '{{%transfer_candidate}}.paid' => 1
            ]);
    }

    /**
     * @return $this
     */
    public function filterUnpaid()
    {
        return $this->andWhere([
                '{{%transfer_candidate}}.paid' => 0
            ]);
    }

    /**
     * @param $company_id
     * @return $this
     */
    public function filterCompanyId($company_id)
    {
        return $this->andWhere(['{{%transfer_candidate}}.company_id' => $company_id]);
    }

    /**
     * @return $this
     */
    public function filterPaidInvoice()
    {
        return $this->andWhere([
            '{{%invoice}}.invoice_status' => 'paid',
            '{{%invoice}}.deleted' => 0
        ]);
    }

	/**
	 * Return profit for transfer 
	 */
	public function profit($transfer_id)
	{
		return $this->andWhere([
                '{{%transfer_candidate}}.transfer_id' => $transfer_id
            ])->sum('(({{%transfer_candidate}}.company_hourly_rate - {{%transfer_candidate}}.candidate_hourly_rate ) * {{%transfer_candidate}}.hours) - {{%transfer_candidate}}.transfer_cost');
            // transfer cost will be on admin  
	}
		
    /**
     * Return candidates who not got paid
     * but his employer have paid to admin  
     */
    public function payable()
    {
        return  $this->joinWith('invoice')
                    ->filterUnpaid()//unpaid candidate
                    ->filterPaidInvoice();//paid invoice
    }

    /**
     * @param $company_id
     * @return $this
     */
    public function groupByCompany($company_id)
    {
        return $this->andWhere(['!=', '{{%transfer_candidate}}.company_id', $company_id])
            ->groupBy('{{%transfer_candidate}}.company_id');
    }

    /**
     * Return candidates for transfer
     * @param $transfer_id
     * @return $this
     */
    public function candidatesByTransfer($transfer_id) 
    {
        return $this->andWhere([
                '{{%transfer_candidate}}.transfer_id' => $transfer_id
            ]);
    }

    /**
     * Total paid in transfer
     * @param $transfer_id
     * @return int|string
     */
    public function totalPaid($transfer_id) 
    {
        return $this->andWhere(['transfer_id' => $transfer_id, 'paid' => 1])
            ->count();
    }

    /**
     * Total unpaid in transfer
     * @param $transfer_id
     * @return int|string
     */
    public function totalUnpaid($transfer_id) 
    {
        return $this->andWhere(['transfer_id' => $transfer_id, 'paid' => 0])
            ->count();
    }

    /**
     * Return unpaid candidate list for a given transfer
     * @param $transfer_id
     * @return $this
     */
    public function unpaid($transfer_id) 
    {
    	return $this->andWhere([
                '{{%transfer_candidate}}.paid' => 0,
                'transfer_id' => $transfer_id
            ]);
    }
}
