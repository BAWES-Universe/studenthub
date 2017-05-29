<?php

use yii\db\Migration;

class m170529_044625_bank_table_changes extends Migration
{
    public function up()
    {
        $this->addColumn('bank','bank_swift_code',$this->text()->after('bank_name')->notNull());
        $this->addColumn('bank','bank_address',$this->string(12)->after('bank_swift_code')->notNull());
    }

    public function down()
    {
        echo "m170529_044625_bank_table_changes cannot be reverted.\n";

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
