<?php

use yii\db\Migration;

/**
 * Class m240205_100901_contact_delete
 */
class m240205_100901_contact_delete extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("contact", "deleted", $this->boolean()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240205_100901_contact_delete cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240205_100901_contact_delete cannot be reverted.\n";

        return false;
    }
    */
}
