<?php

use yii\db\Migration;

/**
 * Class m211027_172434_request_staff
 */
class m211027_172434_request_staff extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn (
            'request',
            'staff_uuid',
            $this->char(60)->after('contact_uuid')
        );

        $this->createIndex(
            'idx-request-staff_uuid',
            'request',
            'staff_uuid'
        );

        $this->addForeignKey(
            'fk-request-staff_uuid',
            'request',
            'staff_uuid',
            'staff',
            'staff_uuid',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m211027_172434_request_staff cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m211027_172434_request_staff cannot be reverted.\n";

        return false;
    }
    */
}
