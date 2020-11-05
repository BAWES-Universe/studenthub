<?php

use yii\db\Migration;

/**
 * Class m201105_122925_video_log
 */
class m201105_122925_video_log extends Migration
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

        $this->createTable('candidate_video_log', [
            "video_log_uuid" => $this->char(60),
            'candidate_id' => $this->integer(11),
            'ip_address' => $this->string(45),
            'created_at' => $this->datetime()->notNull()
        ], $tableOptions);

        $this->addPrimaryKey('PK', 'candidate_video_log', 'video_log_uuid');

        // creates index for column `candidate_id`
        $this->createIndex(
            'idx-candidate_video_log-candidate_id',
            'candidate_video_log',
            'candidate_id'
        );

        // add foreign key for table `candidate_id`
        $this->addForeignKey(
            'fk-candidate_video_log-candidate_id',
            'candidate_video_log',
            'candidate_id',
            'candidate',
            'candidate_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m201105_122925_video_log cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m201105_122925_video_log cannot be reverted.\n";

        return false;
    }
    */
}
