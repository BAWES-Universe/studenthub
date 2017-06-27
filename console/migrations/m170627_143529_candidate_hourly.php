<?php

use yii\db\Migration;

class m170627_143529_candidate_hourly extends Migration
{
    public function safeUp()
    {
        $this->alterColumn("transfer_candidate", "candidate_hourly_rate", $this->decimal(10, 3));
        $this->alterColumn("transfer_candidate", "company_hourly_rate", $this->decimal(10, 3));
    }
}
