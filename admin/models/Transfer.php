<?php
namespace admin\models;

use Yii;
use admin\models\TransferCandidate;
use admin\models\Company;
use admin\models\Invoice;

/**
 * This is the model class for table "Transfer".
 * It extends from \common\models\Transfer but with custom functionality for this application module
 */
class Transfer extends \common\models\Transfer 
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
    	$fields = parent::fields();

        $fields['company_name'] = function($model) {
            return $model->company->company_name;
        };

        $fields['company_email'] = function($model) {
            return $model->company->company_email;
        };

    	$fields['total_transfer_cost'] = function($model) {
    		return TransferCandidate::find()
                ->where([
                    'transfer_id' => $model->transfer_id
                ])
                ->sum('transfer_cost');
    	};

    	unset($fields['deleted']);

    	return $fields;
    }

	
    /**
     * @inheritdoc
     */
    public function extraFields()
    {
        return [
        	'company',
            'invoice',
            'transferCandidates',
            'childTransferInvoices',
            'childTransferCandidates'
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompany()
    {
        return $this->hasOne(Company::className(), ['company_id' => 'company_id']);
    }

    /**
     * Get the invoice belonging to this transfer
     * Each transfer can have max a single invoice, unless it has sub-transfers
     * then each subtransfer can have an invoice each.
     *
     * If this is a parent transfer that has subtransfers, it should show up empty
     * will need to use Transfer::getChildTransferInvoices()
     * @return \yii\db\ActiveQuery|static
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::className(), ['transfer_id'=>'transfer_id']);
    }

    /**
     * Get all TransferCandidate links under this transfer
     * which include hours worked, hourly rate, etc
     *
     * If this is a parent transfer that has subtransfers, it should show up empty
     * will need to use Transfer::getChildTransferCandidates()
     * @return \yii\db\ActiveQuery
     */
    public function getTransferCandidates()
    {
        if($this->parent_transfer_id)
        {
            //child transfer 
            return $this->hasMany(TransferCandidate::className(), ['transfer_id' => 'transfer_id'])
                ->via('parentTransfer')    
                ->andWhere([
                    '{{%transfer_candidate}}.deleted' => 0,
                    '{{%transfer_candidate}}.company_id' => $this->company_id
                ]);       
        }
        else
        {
            //parent transfer 
            return $this->hasMany(TransferCandidate::className(), ['transfer_id' => 'transfer_id'])
                ->andWhere(['{{%transfer_candidate}}.deleted' => 0]);    
        }        
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildTransfers()
    {
        return $this->hasMany(Transfer::className(),['parent_transfer_id'=>'transfer_id'])->andWhere(['{{%transfer}}.deleted'=>0]);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @return \yii\db\ActiveQuery|static
     */
    public function getChildTransferInvoices()
    {
        return $this->hasMany(Invoice::className(), ['transfer_id'=>'transfer_id'])
            ->via('childTransfers');
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @return \yii\db\ActiveQuery|static
     */
    public function getChildTransferCandidates()
    {
        return $this->hasMany(TransferCandidate::className(), ['transfer_id'=>'transfer_id'])->via('childTransfers');
    }
}