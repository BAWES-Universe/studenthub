<?php

use yii\db\Migration;

/**
 * Class m201224_094026_request_staff
 */
class m201224_094026_request_staff extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey ('fk-request-staff_id', 'request');

        $this->dropIndex ('idx-request-staff_id', 'request');

        $this->dropColumn ('request', 'staff_id');

        $this->db->createCommand ('update request set request_status="pending" when request_status="started"');
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
        echo "m201224_094026_request_staff cannot be reverted.\n";

        return false;
    }
    */
}
