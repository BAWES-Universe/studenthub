<?php

namespace common\models\query;
use Yii;

/**
 * This is the ActiveQuery class for [[Invoice]].
 *
 */
class InvoiceQuery extends \yii\db\ActiveQuery
{
    /**
     * Unpaid candidates 
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