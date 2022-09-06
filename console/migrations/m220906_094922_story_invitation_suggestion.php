<?php

use yii\db\Migration;

/**
 * Class m220906_094922_story_invitation_suggestion
 */
class m220906_094922_story_invitation_suggestion extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        Yii::$app->db->createCommand('update `invitation` set story_uuid = (SELECT story_uuid FROM `story` WHERE `story`.`request_uuid` = `invitation`.`request_uuid`)')->execute();
        Yii::$app->db->createCommand('update `suggestion` set story_uuid = (SELECT story_uuid FROM `story` WHERE `story`.`request_uuid` = `suggestion`.`request_uuid`)')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220906_094922_story_invitation_suggestion cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220906_094922_story_invitation_suggestion cannot be reverted.\n";

        return false;
    }
    */
}
