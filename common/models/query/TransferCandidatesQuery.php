<?php

namespace common\models\query;
use Yii;

/**
 * This is the ActiveQuery class for [[TransferCandidates]].
 *
 */
class TransferCandidatesQuery extends \yii\db\ActiveQuery
{
    /**
     * @inheritdoc
     * @return BlockDate[]|array
     */
//    public function all($db = null)
//    {
//        return parent::all($db);
//    }

    /**
     * @inheritdoc
     * @return BlockDate|array|null
     */
//    public function one($db = null)
//    {
//        return parent::one($db);
//    }

	/**
	 * Return profit for transfer 
	 */
	public function profit($transfer_id)
	{
		return $this->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer_id
            ])
            ->sum('(({{%transfer_candidates}}.company_hourly_rate - {{%transfer_candidates}}.candidate_hourly_rate ) * hours) - {{%transfer_candidates}}.transfer_cost');
            // transfer cost will be on admin  
	}
		
    /**
     * Return candiates who not got paid 
     * but his employer have paid to admin  
     */
    public function payable()
    {
        return $this->select('{{%transfer_candidates}}.*, (({{%transfer_candidates}}.candidate_hourly_rate*{{%transfer_candidates}}.hours)+{{%transfer_candidates}}.bonus) as total_amount')
            ->joinWith(['candidate'=>function($query){
                $query->select(['candidate_id','candidate_name','candidate_name_ar','candidate_personal_photo','candidate_email','candidate_phone']);
            }])
            ->where(['{{%transfer_candidates}}.paid' => 0]);
    }

    /**
     *  Return candiates for transfer 
     */
    public function candidatesByTransfer($transfer_id) 
    {
        return $this->select([
        		'{{%transfer_candidates}}.*', 
        		'{{%store}}.store_name', 
        		'{{%company}}.company_name', 
        		'{{%company}}.company_email', 
        		'{{%candidate}}.*',
        		'{{%bank}}.*profit' => '(({{%transfer_candidates}}.company_hourly_rate - {{%transfer_candidates}}.candidate_hourly_rate) * hours) - transfer_cost'
        	])
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->innerJoin('{{%store}}', '{{%store}}.store_id = {{%candidate}}.store_id')
            ->innerJoin('{{%company}}', '{{%store}}.company_id = {{%company}}.company_id')
            ->leftJoin('{{%bank}}', '{{%bank}}.bank_id = {{%candidate}}.bank_id')
            ->where([
                '{{%transfer_candidates}}.transfer_id' => $transfer_id
            ]);            
    }

    /**
     * Filter parent transfer 
     */
    public function parentTransfers() 
    {
        return $this->andWhere('parent_transfer_id IS NULL');
    }

    /**
     * Field require on listing 
     */
    public function selectedFields() 
    {
        return $this->select([
            '{{%transfer}}.*', 
            '{{%company}}.company_name', 
            '{{%company}}.company_email'
        ]);  
    } 

    public function companyJoin() 
    {
        return $this->leftJoin('{{%company}}', '{{%company}}.company_id = {{%transfer}}.company_id');    
    }

    public function filterCompany($company_name)
    {
        return $this->andWhere(['like', '{{%company}}.company_name', $company_name]);    
    }

    public function filterStatus($transfer_status) 
    {
        return $this->andWhere(['{{%transfer}}.transfer_status' => $transfer_status]);
    }    

    /**
     * Total paid in transfer 
     */
    public function totalPaid($transfer_id) 
    {
        return $this->where(['transfer_id' => $transfer_id, 'paid' => 1])
            ->count();
    }

    /**
     * Total unpaid in transfer 
     */
    public function totalUnpaid($transfer_id) 
    {
        return $this->where(['transfer_id' => $transfer_id, 'paid' => 0])
            ->count();
    }

    /**
     * Return unpaid candidate list for a given transfer
     */
    public function unpaid($transfer_id) 
    {
    	return $this->select('{{%candidate}}.candidate_id, {{%candidate}}.candidate_name')
            ->innerJoin('{{%candidate}}', '{{%candidate}}.candidate_id = {{%transfer_candidates}}.candidate_id')
            ->where([
                '{{%transfer_candidates}}.paid' => 0,
                'transfer_id' => $transfer_id
            ]);
    }    	
}
