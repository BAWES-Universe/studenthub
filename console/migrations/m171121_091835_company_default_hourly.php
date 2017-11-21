<?php

use yii\db\Migration;

class m171121_091835_company_default_hourly extends Migration
{
    public function safeUp()
    {
        $sql = "UPDATE company SET company_hourly_rate='2' WHERE company_hourly_rate IS NULL OR company_hourly_rate = 0";
        
        $this->db->createCommand($sql)
            ->execute();
    }

    public function safeDown()
    {
        echo "m171121_091835_company_default_hourly cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m171121_091835_company_default_hourly cannot be reverted.\n";

        return false;
    }
    */
}
