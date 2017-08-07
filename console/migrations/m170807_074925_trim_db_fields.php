<?php

use yii\db\Migration;

class m170807_074925_trim_db_fields extends Migration
{
    public function safeUp()
    {
        $this->execute("UPDATE `candidate` set `candidate_iban` = TRIM(`candidate_iban`)");
        $this->execute("UPDATE `candidate` set `bank_account_name` = TRIM(`bank_account_name`)");
    }

    public function safeDown()
    {
        echo "m170807_074925_trim_db_fields cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170807_074925_trim_db_fields cannot be reverted.\n";

        return false;
    }
    */
}
