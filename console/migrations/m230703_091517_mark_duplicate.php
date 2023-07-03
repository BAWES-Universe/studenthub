<?php

use yii\db\Migration;

/**
 * Class m230703_091517_mark_duplicate
 */
class m230703_091517_mark_duplicate extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate', 'is_duplicate', $this->boolean()->after('deleted')->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m230703_091517_mark_duplicate cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m230703_091517_mark_duplicate cannot be reverted.\n";

        return false;
    }
    */
}
