<?php

use yii\db\Migration;

/**
 * Class m241106_085939_history
 */
class m241106_085939_history extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {


        $columnData = $this
            ->getDb()
            ->getSchema()
            ->getTableSchema('candidate_work_history')
            ->getColumn('deleted');

        if (!$columnData) {
            $this->addColumn("candidate_work_history", "deleted", $this->boolean()->defaultValue(false));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m241106_085939_history cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241106_085939_history cannot be reverted.\n";

        return false;
    }
    */
}
