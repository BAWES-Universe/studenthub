<?php

use common\models\Story;
use yii\db\Migration;

/**
 * Class m220107_120337_cancel_story_if_request
 */
class m220107_120337_cancel_story_if_request extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql = "UPDATE story SET story_status='".Story::STATUS_CANCELLED."' WHERE request_uuid IN 
            (SELECT request_uuid FROM request WHERE request_status='".\common\models\Request::STATUS_CANCELLED."')";

        $this->db->createCommand ($sql)->execute ();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220107_120337_cancel_story_if_request cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220107_120337_cancel_story_if_request cannot be reverted.\n";

        return false;
    }
    */
}
