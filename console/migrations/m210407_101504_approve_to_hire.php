<?php

use yii\db\Migration;

/**
 * Class m210407_101504_approve_to_hire
 */
class m210407_101504_approve_to_hire extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn (
            'company',
            'company_approved_to_hire',
            $this->boolean ()->after('company_last_followup_datetime')->defaultValue (true)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210407_101504_approve_to_hire cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210407_101504_approve_to_hire cannot be reverted.\n";

        return false;
    }
    */
}
