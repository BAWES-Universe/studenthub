<?php

use yii\db\Migration;

/**
 * Class m201009_153032_candidate_video
 */
class m201009_153032_candidate_video extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate', 'candidate_video_processed', $this->boolean()->after('candidate_video')->defaultValue(0));

        $this->db->createCommand('UPDATE candidate SET candidate_video_processed=1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201009_153032_candidate_video cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201009_153032_candidate_video cannot be reverted.\n";

        return false;
    }
    */
}
