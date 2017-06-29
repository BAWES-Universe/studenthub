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
            'invoices',
            'transferCandidates',
            'childTransferInvoices',
            'childTransferCandidates'
        ];
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCompany($modelClass = "\admin\models\Company")
    {
        return parent::getCompany($modelClass);
    }

    /**
     * Get all TransferCandidate links under this transfer
     * which include hours worked, hourly rate, etc
     *
     * If this is a parent transfer that has subtransfers, it should show up empty
     * will need to use Transfer::getChildTransferCandidates()
     * @param string $modelClass
     * @return $this|\yii\db\ActiveQuery
     */
    public function getTransferCandidates($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getTransferCandidates($modelClass);
    }

    /**
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransfers($modelClass = "\admin\models\Transfer")
    {
        return parent::getChildTransfers($modelClass)->andWhere(['{{%transfer}}.deleted'=>0]);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferInvoices($modelClass = "\admin\models\Invoice")
    {
        return parent::getChildTransferInvoices($modelClass);
    }

    /**
     * Get all invoices belonging to child transfers (if available)
     * @param string $modelClass
     * @return $this
     */
    public function getChildTransferCandidates($modelClass = "\admin\models\TransferCandidate")
    {
        return parent::getChildTransferCandidates($modelClass);
    }
}