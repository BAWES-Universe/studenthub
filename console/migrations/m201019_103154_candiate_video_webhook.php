<?php

use yii\db\Migration;

/**
 * Class m201019_103154_candiate_video_webhook
 */
class m201019_103154_candiate_video_webhook extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate', 'candidate_video_job_id', $this->string()->after('candidate_video')->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201019_103154_candiate_video_webhook cannot be reverted.\n";

        return false;
    }
    */
}
