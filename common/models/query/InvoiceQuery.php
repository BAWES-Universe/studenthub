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
    /**
     * @param null $db
     * @return array|\yii\db\ActiveRecord[]
     */
    public function all($db = null)
    {
        $this->andWhere(['{{%invoice}}.deleted' => 0]);
        return parent::all($db);
    }

    /**
     * @param null $db
     * @return array|null|\yii\db\ActiveRecord
     */
    public function one($db = null)
    {
        $this->andWhere(['{{%invoice}}.deleted' => 0]);
        return parent::one($db);
    }

    /**
     * @param $company_ids
     * @return $this
     */
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
     * @param $company
     * @return $this
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
     * @return $this
     */
    public function paid() 
    {
        return $this->andWhere(['{{%invoice}}.invoice_status' => 'paid']);
    }

    /**
     * Unpaid Invoice
     * @return $this
     */
    public function unpaid() 
    {
        return $this->andWhere(['{{%invoice}}.invoice_status' => 'unpaid']);
    }

    /**
     * Return invoice with transfer
     * @param $invoice_id
     * @return $this
     */
    public function withTransfer($invoice_id)
    {
        return $this->select([
                '{{%invoice}}.*', 
                '{{%transfer}}.*'
            ])
            ->innerJoin('{{%transfer}}', '{{%transfer}}.transfer_id = {{%invoice}}.transfer_id')
            ->andWhere(['{{%invoice}}.invoice_id' => $invoice_id]);
    }

    /**
     * Return Invoice by transfer id
     * @param $transfer_id
     * @return $this
     */
    public function byTransfer($transfer_id)
    {
        return $this->innerJoin('transfer', 'transfer.transfer_id = invoice.transfer_id')
            ->andWhere(['transfer.transfer_id' => $transfer_id])
            ->orWhere(['transfer.parent_transfer_id' => $transfer_id]);
    }
}