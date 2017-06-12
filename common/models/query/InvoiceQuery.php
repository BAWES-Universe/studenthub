<?php

namespace common\models\query;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the ActiveQuery class for [[Invoice]].
 *
 */
class InvoiceQuery extends \yii\db\ActiveQuery
{
    public function all($db = null)
    {
        $this->andWhere(['{{%invoice}}.deleted' => 0]);
        return parent::all($db);
    }

    public function one($db = null)
    {
        $this->andWhere(['{{%invoice}}.deleted' => 0]);
        return parent::one($db);
    }

    public function filterCompanies($company_ids)
    {
        return $this->andWhere([
            'in', 
            '{{%transfer}}.company_id', 
            $company_ids
        ]);
    }    

    /**
     * Invoice for login company /his childs
     */
    public function filterCurrentCompany($company) 
    {
        $companies = $company->subCompanies;

        $company_ids = ArrayHelper::map(
            $companies, 
            'company_id', 
            'company_id'
        );

        $company_ids[] = $company->company_id;

        return $this->andWhere([
            'in', 
            '{{%transfer}}.company_id', 
            $company_ids
        ]);
    }

    /**
     * Paid Invoice 
     */
    public function paid() 
    {
        return $this->where(['{{%invoice}}.invoice_status' => 'paid']);
    }

    /**
     * Unpaid Invoice 
     */
    public function unpaid() 
    {
        return $this->where(['{{%invoice}}.invoice_status' => 'unpaid']);
    }

    /**
     * Return invoice with transfer 
     * @param $invoice_id 
     */
    public function withTransfer($invoice_id)
    {
        return $this->select([
                '{{%invoice}}.*', 
                '{{%transfer}}.*'
            ])
            ->innerJoin('{{%transfer}}', '{{%transfer}}.transfer_id = {{%invoice}}.transfer_id')
            ->where(['{{%invoice}}.invoice_id' => $invoice_id]);
    }        

    /**
     * Return Invoice by transfer id 
     */
    public function byTransfer($transfer_id)
    {
        return $this->innerJoin('transfer', 'transfer.transfer_id = invoice.transfer_id')
            ->where(['transfer.transfer_id' => $transfer_id])
            ->orWhere(['transfer.parent_transfer_id' => $transfer_id]);
    }
}