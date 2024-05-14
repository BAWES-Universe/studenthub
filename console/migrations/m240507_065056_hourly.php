<?php

use yii\db\Migration;

/**
 * Class m240507_065056_hourly
 */
class m240507_065056_hourly extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate_work_history", "company_hourly_rate",
            $this->decimal(12,3)->null()->after('candidate_hourly_rate'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240507_065056_hourly cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240507_065056_hourly cannot be reverted.\n";

        return false;
    }
    */
}
