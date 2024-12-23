<?php

use yii\db\Migration;

/**
 * Class m241223_083804_appeal
 */
class m241223_083804_appeal extends Migration
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

        //table: candidate_working_hour_appeal

        $this->createTable('{{%candidate_working_hour_appeal}}', [
            "appeal_uuid" => $this->char(60),
            "candidate_working_hour_uuid" => $this->char(60)->notNull(),
            "candidate_id" => $this->integer(11)->notNull(),
            "reason" => $this->text(),
            "status" =>  $this->tinyInteger(2)->notNull()->defaultValue(0),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-cwha-candidate_working_hour_uuid',
                'candidate_working_hour_appeal', "appeal_uuid");

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-cwha-candidate_id',
            'candidate_working_hour_appeal',
            'candidate_id'
        );

        // add foreign key for table `candidate`
        $this->addForeignKey(
            'fk-cwha-candidate_id',
            'candidate_working_hour_appeal',
            'candidate_id',
            'candidate',
            'candidate_id'
        );

        // creates index for column `candidate_working_hour_uuid`
        $this->createIndex(
            'idx-cwha-candidate_working_hour_uuid',
            'candidate_working_hour_appeal',
            'candidate_working_hour_uuid'
        );

        // add foreign key for table `candidate_working_hour`
        $this->addForeignKey(
            'fk-cwha-candidate_working_hour_uuid',
            'candidate_working_hour_appeal',
            'candidate_working_hour_uuid',
            'candidate_working_hour',
            'candidate_working_hour_uuid'
        );

        //table: candidate_working_hour_appeal_updates

        $this->createTable('{{%candidate_working_hour_appeal_updates}}', [
            "appeal_update_uuid" => $this->char(60),
            "appeal_uuid" => $this->char(60)->notNull(),
           // "candidate_working_hour_uuid" => $this->char(60)->notNull(),
            "update" => $this->string(),
            "detail"=> $this->text(),
            "created_at" => $this->dateTime(),
            "updated_at" => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('pk-cwhau-appeal_update_uuid',
            "candidate_working_hour_appeal_updates", "appeal_update_uuid");

        $this->createIndex(
            'idx-candidate_working_hour_appeal_updates-appeal_uuid',
            'candidate_working_hour_appeal_updates',
            'appeal_uuid'
        );

        $this->addForeignKey(
            'fk-candidate_working_hour_appeal_updates-appeal_uuid',
            'candidate_working_hour_appeal_updates',
            'appeal_uuid',
            'candidate_working_hour_appeal',
            'appeal_uuid',
            "CASCADE",
            "CASCADE"
        );

        //table: candidate_working_hour

        $this->addColumn("candidate_working_hour", "appeal_uuid", $this->char(60)->null());

        $this->createIndex(
            'idx-candidate_working_hour-appeal_uuid',
            'candidate_working_hour',
            'appeal_uuid'
        );

        // add foreign key for table `candidate_working_hour`
        $this->addForeignKey(
            'fk-candidate_working_hour-appeal_uuid',
            'candidate_working_hour',
            'appeal_uuid',
            'candidate_working_hour_appeal',
            'appeal_uuid'
        );

        $this->addColumn("candidate_notification", "appeal_uuid", $this->char(60)->null());

        $this->createIndex(
            'idx-candidate_notification-appeal_uuid',
            'candidate_notification',
            'appeal_uuid'
        );

        // add foreign key for table `candidate_working_hour`
        $this->addForeignKey(
            'fk-candidate_notification-appeal_uuid',
            'candidate_notification',
            'appeal_uuid',
            'candidate_working_hour_appeal',
            'appeal_uuid'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%candidate_working_hour_appeal_updates}}');
        $this->dropTable('{{%candidate_working_hour_appeal}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241223_083804_appeal cannot be reverted.\n";

        return false;
    }
    */
}
