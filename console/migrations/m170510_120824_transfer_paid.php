<?php

use yii\db\Migration;

class m170510_120824_transfer_paid extends Migration
{
    public function up()
    {
        $this->addColumn(
            "transfer_candidates", 
            "paid", 
            $this->smallInteger(1)->after('transfer_cost')->notNull()->defaultValue(0)
        );
    }
}
