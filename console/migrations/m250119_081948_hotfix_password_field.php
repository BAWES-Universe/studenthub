<?php

use yii\db\Migration;

/**
 * Class m250119_081948_hotfix_password_field
 */
class m250119_081948_hotfix_password_field extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //$this->alterColumn('contact', 'contact_password_hash', $this->string(255)->null());
        //$this->alterColumn('staff', 'staff_password_hash', $this->string(255)->null());
        //store_manager password_hash
        $this->alterColumn('candidate', 'candidate_password_hash', $this->string(255)->null());     
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250119_081948_hotfix_password_field cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250119_081948_hotfix_password_field cannot be reverted.\n";

        return false;
    }
    */
}
