<?php

use yii\db\Migration;

/**
 * Class m201026_105629_request_staff
 */
class m201026_105629_request_staff extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('request', 'staff_id', $this->integer(11)->after('contact_uuid'));

        $this->createIndex(
            'idx-request-staff_id',
            'request',
            'staff_id'
        );

        $this->addForeignKey(
            'fk-request-staff_id',
            'request',
            'staff_id',
            'staff',
            'staff_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201026_105629_request_staff cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201026_105629_request_staff cannot be reverted.\n";

        return false;
    }
    */
}
