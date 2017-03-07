<?php

use yii\db\Migration;

class m170307_121642_candidate_phone extends Migration
{
    public function up()
    {
        $this->addColumn('candidate', 'candidate_phone', $this->string(20)->after('candidate_email'));

        $this->addColumn('candidate', 'bank_account_name', $this->string(100)->after('bank_id'));
    }

    public function down()
    {
        $this->dropColumn('candidate', 'candidate_phone');

        $this->dropColumn('candidate', 'bank_account_name');
    }
}
