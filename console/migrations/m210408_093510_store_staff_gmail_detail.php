<?php

use yii\db\Migration;

/**
 * Class m210408_093510_store_staff_gmail_detail
 */
class m210408_093510_store_staff_gmail_detail extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('staff','staff_gmail_username',$this->char(225)->after('staff_password_hash')->null());
        $this->addColumn('staff','staff_gmail_password',$this->char(225)->after('staff_gmail_username')->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('staff','staff_gmail_username');
        $this->dropColumn('staff','staff_gmail_password');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210408_093510_store_staff_gmail_detail cannot be reverted.\n";

        return false;
    }
    */
}
