<?php

use yii\db\Migration;

/**
 * Class m221010_073207_story_table_changes
 */
class m221010_073207_story_table_changes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        $records = Yii::$app->db->createCommand('select * from story where story_status = 0')->queryAll(); // unstarted
        foreach ($records as $record) {
            $story_uuid = $record['story_uuid'];

            $activities = Yii::$app->db->createCommand("select * from story_activity where story_uuid= '$story_uuid' and activity_status = 8")->queryOne();

            if ($activities) {
                // except first record set all other 0 record has stopped
                $story_uuid = $activities['story_uuid'];

                $q = "update `story` set story_status = 8 where story_uuid = '$story_uuid'";
                Yii::$app->db->createCommand($q)->execute();
            }
        }

        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m221010_073207_story_table_changes cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m221010_073207_story_table_changes cannot be reverted.\n";

        return false;
    }
    */
}
