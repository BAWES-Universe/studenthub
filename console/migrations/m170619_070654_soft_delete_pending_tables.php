<?php

use yii\db\Migration;

class m170619_070654_soft_delete_pending_tables extends Migration
{
    public function up()
    {
        $this->addColumn('store', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('store_updated_at'));
        $this->addColumn('candidate', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('candidate_updated_at'));
        $this->addColumn('university', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('university_name_ar'));
        $this->addColumn('company', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('company_updated_at'));
        $this->addColumn('bank', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('bank_transfer_type'));
        $this->addColumn('staff', 'deleted', $this->smallInteger(1)->defaultValue(0)->notNull()->after('staff_updated_at'));
    }

    public function down()
    {
        echo "m170619_070654_soft_delete_pending_tables cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
