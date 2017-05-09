<?php

use yii\db\Migration;

class m170424_123809_invoice_payment_received extends Migration
{
    public function up()
    {
        $this->addColumn('invoice', 'payment_received_on', $this->date()->after('invoice_status'));        
    }
}
