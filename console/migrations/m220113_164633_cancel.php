<?php

use yii\db\Migration;
use common\models\Story;
use common\models\Request;

/**
 * Class m220113_164633_cancel
 */
class m220113_164633_cancel extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $sql1 = 'UPDATE story SET story_status="'.Story::STATUS_CANCELLED.'" WHERE request_uuid IN (SELECT requests_uuid
            FROM request WHERE request_status="'.Request::STATUS_CANCELLED.'")';

        Yii::$app->db->createCommand($sql1);

        $sql1 = 'UPDATE story SET story_status="'.Story::STATUS_DELIVERED.'" WHERE request_uuid IN (SELECT requests_uuid
            FROM request WHERE request_status="'.Request::STATUS_DELIVERED.'")';

        Yii::$app->db->createCommand($sql1);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220113_164633_cancel cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220113_164633_cancel cannot be reverted.\n";

        return false;
    }
    */
}
