<?php

use yii\db\Migration;
use common\models\InvoiceCandidates;

class m170302_141628_transfer_cost extends Migration
{
    public function up()
    {
        $this->addColumn('invoice_candidates', 'transfer_cost', $this->decimal(10, 3)->after('bonus'));
    }

    public function down()
    {
        
    }
}
