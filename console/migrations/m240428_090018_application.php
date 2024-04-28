<?php

use yii\db\Migration;

/**
 * Class m240428_090018_application
 */
class m240428_090018_application extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('request_application', [
            "application_uuid" => $this->char(60),
            'request_uuid' => $this->char(60)->notNull(),
            "fulltimer_uuid" => $this->char(60)->null(),
            "candidate_id" => $this->integer(11)->null(),
            "status" => $this->tinyInteger(2)->defaultValue(0),
            'created_at' => $this->datetime(),
            'updated_at' => $this->datetime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'request_application', 'application_uuid');

        // creates index for column `request_uuid`
        $this->createIndex(
            'idx-request_application-request_uuid',
            'request_application',
            'request_uuid'
        );

        // add foreign key for table `request_uuid`
        $this->addForeignKey(
            'fk-request_application-request_uuid',
            'request_application',
            'request_uuid',
            'request',
            'request_uuid',
            'CASCADE'
        );

        // creates index for column `fulltimer_uuid`
        $this->createIndex(
            'idx-request_application-fulltimer_uuid',
            'request_application',
            'fulltimer_uuid'
        );

        // add foreign key for table `request_uuid`
        $this->addForeignKey(
            'fk-request_application-fulltimer_uuid',
            'request_application',
            'fulltimer_uuid',
            'fulltimer',
            'fulltimer_uuid',
            'CASCADE'
        );

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-request_application-candidate_id',
            'request_application',
            'candidate_id'
        );

        // add foreign key for table `candidate_id`
        $this->addForeignKey(
            'fk-request_application-candidate_id',
            'request_application',
            'candidate_id',
            'candidate',
            'candidate_id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m240428_090018_application cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m240428_090018_application cannot be reverted.\n";

        return false;
    }
    */
}
