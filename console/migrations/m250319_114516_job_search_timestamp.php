<?php

use yii\db\Migration;

/**
 * Class m250319_114516_job_search_timestamp
 */
class m250319_114516_job_search_timestamp extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn("candidate", "candidate_job_search_updated_at",
            $this->dateTime()->null()->after("candidate_job_search_status"));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250319_114516_job_search_timestamp cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250319_114516_job_search_timestamp cannot be reverted.\n";

        return false;
    }
    */
}
