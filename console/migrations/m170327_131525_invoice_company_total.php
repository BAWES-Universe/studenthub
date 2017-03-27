<?php

use yii\db\Migration;

class m170327_131525_invoice_company_total extends Migration
{
    public function up()
    {
        $this->addColumn('invoice', 'company_total', $this->decimal(12, 3)->after('total'));

        //for each invoice 
        
        $invoices = Yii::$app->db->createCommand('select * from invoice where invoice_status != "10"')->queryAll();

        //for each candidates 

        foreach ($invoices as $key => $invoice) 
        {            
            //calculate company total 

            $total = 0;

            $candidates = Yii::$app->db->createCommand('select * from invoice_candidates where invoice_id = "'.$invoice['invoice_id'].'"')->queryAll();

            foreach ($candidates as $key => $candidate) 
            {
                $total += $candidate['bonus'] + ($candidate['hours'] * Yii::$app->params['candidate_max_hourly_rate']) + Yii::$app->params['transfer_cost'];
            }

            //update company total for invoice 

            Yii::$app->db->createCommand('update invoice set company_total="'.$total.'" where invoice_id="'.$invoice['invoice_id'].'"')->execute();
        }
    }
}
