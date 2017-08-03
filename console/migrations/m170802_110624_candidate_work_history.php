<?php

use yii\db\Migration;

class m170802_110624_candidate_work_history extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('candidate_work_history', [
            'id' => $this->primaryKey(),
            'candidate_id' => $this->integer(11),
            'store_id' => $this->integer(11),
            'start_date' => $this->date(),
            'end_date' => $this->date(),
            'candidate_hourly_rate' => $this->decimal(12, 3),
        ], $tableOptions);
    }

    public function safeDown()
    {
        $this->dropTable('candidate_work_history');

        return false;
    }


    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m170802_110624_candidate_work_history cannot be reverted.\n";

        return false;
    }
    */
}
