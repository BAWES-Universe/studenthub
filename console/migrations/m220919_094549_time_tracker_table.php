<?php

use yii\db\Migration;

/**
 * Class m220919_094549_time_tracker_table
 */
class m220919_094549_time_tracker_table extends Migration
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

        $this->createTable('{{%candidate_working_hour}}', [
            'candidate_working_hour_uuid' => $this->char(60),
            'candidate_id' => $this->integer(11),
            'store_id' => $this->integer(11),
            'date' => $this->date(),
            'start_time' => $this->time(),
            'end_time' => $this->time(),
            'total_time' => $this->integer(11),
            'start_location_lat' => $this->decimal(10, 8),
            'start_location_long' => $this->decimal(11, 8),
            'end_location_lat' => $this->decimal(10, 8),
            'end_location_long' => $this->decimal(11, 8),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'candidate_working_hour', 'candidate_working_hour_uuid');

        $this->createIndex(
            'idx-candidate_working_hour-candidate_id',
            'candidate_working_hour',
            'candidate_id'
        );

        $this->addForeignKey(
            'fk-candidate_working_hour-candidate_id',
            'candidate_working_hour',
            'candidate_id',
            'candidate',
            'candidate_id',
            'SET NULL'
        );

        $this->createIndex(
            'idx-candidate_working_hour-store_id',
            'candidate_working_hour',
            'store_id'
        );

        $this->addForeignKey(
            'fk-candidate_working_hour-store_id',
            'candidate_working_hour',
            'store_id',
            'store',
            'store_id',
            'SET NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-candidate_working_hour-candidate_id',
            'candidate_working_hour'
        );
        $this->dropIndex(
            'idx-candidate_working_hour-candidate_id',
            'candidate_working_hour'
        );

        $this->dropTable('{{%candidate_working_hour}}');

    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m220919_094549_time_tracker_table cannot be reverted.\n";

        return false;
    }
    */
}
