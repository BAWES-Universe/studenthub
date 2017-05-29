<?php

use yii\db\Migration;

class m170529_071050_candidate_address_line1 extends Migration
{
    public function up()
    {
        $this->addColumn('candidate','candidate_address_line1',$this->text()->null()->after('candidate_phone'));
    }

    public function down()
    {
        $this->dropColumn('candidate','candidate_address_line1');
        return false;
    }
}
