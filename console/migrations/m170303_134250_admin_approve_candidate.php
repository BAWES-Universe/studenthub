<?php

use yii\db\Migration;

class m170303_134250_admin_approve_candidate extends Migration
{
    public function up()
    {
        $this->addColumn('candidate', 'approved', $this->smallInteger(1)->after('candidate_status')->notNull());
    }

    public function down()
    {
        $this->dropColumn('candidate', 'approved');  
    }
}
