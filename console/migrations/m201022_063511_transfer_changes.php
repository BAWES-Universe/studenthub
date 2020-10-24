<?php

use yii\db\Migration;

/**
 * Class m201022_063511_transfer_changes
 */
class m201022_063511_transfer_changes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('transfer','start_date',$this->date()->null()->after('transfer_status'));
        $this->addColumn('transfer','end_date',$this->date()->null()->after('start_date'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201022_063511_transfer_changes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201022_063511_transfer_changes cannot be reverted.\n";

        return false;
    }
    */
}
