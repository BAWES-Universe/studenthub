<?php

use yii\db\Migration;

/**
 * Class m200821_114940_followup
 */
class m200821_114940_followup extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%company}}', 'company_followup', $this->boolean()->defaultValue(true)->after('company_bonus_commission'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200821_114940_followup cannot be reverted.\n";

        return false;
    }
    */
}
