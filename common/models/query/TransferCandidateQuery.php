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
        $this->andWhere(['{{%transfer_candidate}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%transfer_candidate}}.deleted' => 0]);
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
    public function filterCompany($company_id)
    {
        return $this->andWhere([
                '{{%store}}.company_id' => $company_id
            ]);
    }

    /**
     * @param $company_id
     * @return $this
     */
    public function filterCompanyId($company_id)
    {
        return $this->andWhere(['{{%company}}.company_id' => $company_id]);
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
        return  $this->select('{{%transfer_candidate}}.*')
                    ->addSelect('(({{%transfer_candidate}}.candidate_hourly_rate*{{%transfer_candidate}}.hours)+{{%transfer_candidate}}.bonus) as total_amount')
                    ->joinWith(['candidate'=>function($query){
                        $query->select([
                            'candidate_id',
                            'candidate_name',
                            'candidate_name_ar',
                            'candidate_personal_photo',
                            'candidate_email',
                            'candidate_phone',
                            'bank_account_name',
                            'candidate_iban',
                            'bank_id'
                        ]);
                    }])
                    ->joinWith('invoice')
                    ->filterUnpaid()//unpaid candidate
                    ->filterPaidInvoice();//paid invoice
    }

    /**
     * @param $company_id
     * @return $this
     */
    public function groupByCompany($company_id)
    {
        return $this->groupBy('{{%company}}.company_id')
            ->andWhere(['!=', '{{%company}}.company_id', $company_id])
            ->distinct();
    }


    /**
     * Return candidates for transfer
     * @param $transfer_id
     * @return $this
     */
    public function candidatesByTransfer($transfer_id) 
    {
        return $this->select([
                '{{%transfer_candidate}}.*', 
        		'{{%store}}.company_id',
                '{{%store}}.store_name', 
        		'{{%company}}.company_name', 
        		'{{%company}}.company_email', 
        		'{{%candidate}}.candidate_name',
        		'{{%candidate}}.candidate_name_ar',
                '{{%candidate}}.candidate_email',
                '{{%candidate}}.bank_account_name',
                '{{%candidate}}.candidate_iban',
                '{{%candidate}}.candidate_personal_photo',
                '{{%candidate}}.candidate_phone',
                '{{%candidate}}.candidate_birth_date',
                '{{%candidate}}.candidate_address_line1',
                '{{%candidate}}.candidate_civil_id',
                '{{%candidate}}.candidate_civil_expiry_date',
                '{{%candidate}}.candidate_civil_photo_front',
                '{{%candidate}}.candidate_civil_photo_back',
                '{{%candidate}}.candidate_status',
                '{{%candidate}}.approved',
                '{{%candidate}}.candidate_created_at',
                '{{%candidate}}.candidate_updated_at',
                '{{%bank}}.*',
        		'profit as' => '(({{%transfer_candidate}}.company_hourly_rate - {{%transfer_candidate}}.candidate_hourly_rate) * hours) - transfer_cost'
        	])
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidate}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->leftJoin('{{%bank}}', '{{%bank}}.bank_id = {{%candidate}}.bank_id')
            ->andWhere([
                '{{%transfer_candidate}}.transfer_id' => $transfer_id
            ]);
    }

    /**
     * Total paid in transfer 
     */
    public function totalPaid($transfer_id) 
    {
        return $this->andWhere(['transfer_id' => $transfer_id, 'paid' => 1])
            ->count();
    }

    /**
     * Total unpaid in transfer 
     */
    public function totalUnpaid($transfer_id) 
    {
        return $this->andWhere(['transfer_id' => $transfer_id, 'paid' => 0])
            ->count();
    }

    /**
     * Return unpaid candidate list for a given transfer
     */
    public function unpaid($transfer_id) 
    {
    	return $this->select('{{%candidate}}.candidate_id, {{%candidate}}.candidate_name')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidate}}.candidate_id')
            ->andWhere([
                '{{%transfer_candidate}}.paid' => 0,
                'transfer_id' => $transfer_id
            ]);
    }
}
