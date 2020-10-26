<?php

use yii\db\Migration;

/**
 * Class m201023_094550_request_detail
 */
class m201023_094550_request_activities extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        Yii::$app->db->createCommand('SET foreign_key_checks = 0')->execute();
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

//        $this->truncateTable('request');

        $this->createTable('request_activity', [
            'activity_uuid' => $this->char(60)->notNull(),
            'request_uuid' => $this->char(60)->notNull(), // Which user made request?
            'staff_id' => $this->integer(), // Which staff member is assigned to handle it
            'activity_detail' => $this->text()->notNull(),
            'activity_created_datetime' => $this->dateTime()->notNull(),
            'activity_updated_datetime' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'request_activity', 'activity_uuid');

        // creates index for column `staff_id`
        $this->createIndex(
            'idx-request_activity-staff_id',
            'request_activity',
            'staff_id'
        );
//        // add foreign key for table `staff`
        $this->addForeignKey(
            'fk-request_activity-staff_id',
            'request_activity',
            'staff_id',
            'staff',
            'staff_id',
            'CASCADE'
        );
//
//        // creates index for column `request_uuid`
        $this->createIndex(
            'idx-request_activity-request_uuid',
            'request_activity',
            'request_uuid'
        );
        // add foreign key for table `request`
        $this->addForeignKey(
            'fk-request_activity-request_uuid',
            'request_activity',
            'request_uuid',
            'request',
            'request_uuid',
            'CASCADE'
        );
        Yii::$app->db->createCommand('SET foreign_key_checks = 1')->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-request_activity-staff_id','request_activity');
        $this->dropForeignKey('fk-request_activity-request_uuid','request_activity');

        $this->dropTable('request_activity');
    }


    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201023_094550_request_detail cannot be reverted.\n";

        return false;
    }
    */
}
