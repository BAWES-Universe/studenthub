<?php

use yii\db\Migration;

/**
 * Class m250307_084348_automatic_transfer
 */
class m250307_084348_automatic_transfer extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("contract", "auto_generate",
            $this->boolean()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250307_084348_automatic_transfer cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250307_084348_automatic_transfer cannot be reverted.\n";

        return false;
    }
    */
}
