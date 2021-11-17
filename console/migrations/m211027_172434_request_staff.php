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

        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
   
//        $this->dropColumn('request','staff_uuid');
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();

        $this->addColumn (
            'request',
            'staff_id',
            $this->integer(20)->after('contact_uuid')
        );

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
            'staff_id',
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
