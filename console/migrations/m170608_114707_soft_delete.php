<?php

use yii\db\Migration;

class m170608_114707_soft_delete extends Migration
{
    public function up()
    {
        $this->addColumn('invoice', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('invoice_status'));  

        $this->addColumn('transfer', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('transfer_updated_at'));

        $this->addColumn('transfer_candidates', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('tc_updated_at'));
    }
}
