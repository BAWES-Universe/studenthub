<?php

use yii\db\Migration;

/**
 * Class m200911_134742_store_location
 */
class m200911_134742_store_location extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
         
        $this->addColumn('store', 'store_location', $this->string()->after('store_name')->notNull());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200911_134742_store_location cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200911_134742_store_location cannot be reverted.\n";

        return false;
    }
    */
}
