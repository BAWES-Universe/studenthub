<?php

use yii\db\Migration;

/**
 * Class m200807_134023_candidate_job_search
 */
class m200807_134023_candidate_job_search extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn(
            'candidate', 
            'candidate_job_search_status', 
            $this->tinyInteger()->defaultValue(1)->after('candidate_language_pref')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200807_134023_candidate_job_search cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m200807_134023_candidate_job_search cannot be reverted.\n";

        return false;
    }
    */
}
