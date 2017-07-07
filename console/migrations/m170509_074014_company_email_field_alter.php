<?php

use yii\db\Migration;

class m170509_074014_company_email_field_alter extends Migration
{
    public function up()
    {
        $this->alterColumn('company','company_email',$this->string(225)->null());
    }

    public function down()
    {
        echo "m170509_074014_company_email_field_alter cannot be reverted.\n";

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
