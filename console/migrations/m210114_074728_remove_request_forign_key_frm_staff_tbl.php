<?php

use yii\db\Migration;

/**
 * Class m210114_074728_remove_request_forign_key_frm_staff_tbl
 */
class m210114_074728_remove_request_forign_key_frm_staff_tbl extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // getting issue while adding request from company app. so need to remove staff
        // table link
        $this->dropForeignKey('fk-request-request_created_by', 'request');
        $this->dropForeignKey('fk-request-request_updated_by', 'request');

        // getting issue while adding request from company app. so need to remove staff
        // table link
        $this->dropForeignKey('fk-note-created_by', 'note');
        $this->dropForeignKey('fk-note-updated_by', 'note');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m210114_074728_remove_request_forign_key_frm_staff_tbl cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m210114_074728_remove_request_forign_key_frm_staff_tbl cannot be reverted.\n";

        return false;
    }
    */
}
