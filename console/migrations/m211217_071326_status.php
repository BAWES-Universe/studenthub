<?php

use yii\db\Migration;

/**
 * Class m211217_071326_status
 */
class m211217_071326_status extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn ('company', 'company_status_override',
            $this->boolean ()->after ('company_approved_to_hire')->defaultValue (false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211217_071326_status cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211217_071326_status cannot be reverted.\n";

        return false;
    }
    */
}