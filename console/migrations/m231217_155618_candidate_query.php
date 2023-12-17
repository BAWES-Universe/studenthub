<?php

use yii\db\Migration;

/**
 * Class m231217_155618_candidate_query
 */
class m231217_155618_candidate_query extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('candidate', 'is_incomplete_profile',
            $this->boolean()->defaultValue(0)->after('candidate_pending_profile'));

        $sql = "UPDATE candidate SET is_incomplete_profile = 1 WHERE candidate_pending_profile IS NOT NULL AND
        candidate_pending_profile != ''";

        Yii::$app->db->createCommand($sql)->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m231217_155618_candidate_query cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231217_155618_candidate_query cannot be reverted.\n";

        return false;
    }
    */
}
