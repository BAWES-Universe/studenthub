<?php

use yii\db\Migration;

/**
 * Class m221007_123441_story_changes_for_rework_stopped
 */
class m221007_123441_story_changes_for_rework_stopped extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        
        $records = Yii::$app->db->createCommand('select * from story')->queryAll();

        foreach ($records as $record) {
            $story_uuid = $record['story_uuid'];

            $activities = Yii::$app->db->createCommand("select * from story_activity where story_uuid= '$story_uuid' order by activity_created_at ASC")->queryOne();

            if(!$activities) {
                continue;
            }

            // except first record set all other 0 record has stopped
            $story_activity_uuid = $activities['story_activity_uuid'];
            $story_uuid = $activities['story_uuid'];

            $q = "update `story_activity` set activity_status = 8 where (activity_time_spent=0 and activity_status=0) ";
            $q .= "and story_activity_uuid != '$story_activity_uuid' ";
            $q .= "and story_uuid = '$story_uuid'";
            Yii::$app->db->createCommand($q)->execute();
        }

        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221007_123441_story_changes_for_rework_stopped cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221007_123441_story_changes_for_rework_stopped cannot be reverted.\n";

        return false;
    }
    */
}
