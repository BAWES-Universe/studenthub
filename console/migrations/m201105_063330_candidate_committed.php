<?php

use yii\db\Migration;

/**
 * Class m201105_063330_candidate_committed
 */
class m201105_063330_candidate_committed extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        
        $this->addColumn('candidate', 'candidate_committed', $this->boolean()->defaultValue(true)->notNull()->after('candidate_job_search_status'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201105_063330_candidate_committed cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201105_063330_candidate_committed cannot be reverted.\n";

        return false;
    }
    */
}
